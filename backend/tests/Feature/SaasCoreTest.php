<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\Membership;
use App\Models\Site;
use App\Models\User;
use App\Models\Workspace;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SaasCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_creates_complete_tenant_foundation(): void
    {
        $response = $this->post('/register', [
            'name'=>'Alice Owner',
            'email'=>'alice@example.test',
            'password'=>'StrongPass123!',
            'password_confirmation'=>'StrongPass123!',
            'workspace_name'=>'Alice Media',
        ]);

        $response->assertRedirect(route('app.dashboard'));
        $user = User::where('email','alice@example.test')->firstOrFail();
        $workspace = Workspace::where('owner_id',$user->id)->firstOrFail();
        $this->assertDatabaseHas('memberships',['workspace_id'=>$workspace->id,'user_id'=>$user->id,'role'=>'owner']);
        $this->assertDatabaseHas('sites',['workspace_id'=>$workspace->id,'slug'=>'main','status'=>'active']);
        $this->assertTrue($workspace->trial_ends_at->isFuture());
    }

    public function test_tenant_cannot_open_another_workspaces_content_editor(): void
    {
        [$userA,$workspaceA,$siteA] = $this->tenant('alpha','alpha@example.test');
        [, $workspaceB, $siteB] = $this->tenant('beta','beta@example.test');
        $foreign = ContentItem::create(['workspace_id'=>$workspaceB->id,'site_id'=>$siteB->id,'title'=>'Private Beta Draft','slug'=>'private-beta','status'=>'draft','body'=>[]]);

        $response = $this->actingAs($userA)->withSession(['workspace_id'=>$workspaceA->id,'site_id'=>$siteA->id])->get('/app/content/'.$foreign->id.'/edit');
        $response->assertNotFound();
    }

    public function test_hosted_site_urls_are_scoped_by_workspace_even_when_site_slugs_match(): void
    {
        [, $workspaceA, $siteA] = $this->tenant('alpha','alpha@example.test');
        [, $workspaceB, $siteB] = $this->tenant('beta','beta@example.test');
        ContentItem::create(['workspace_id'=>$workspaceA->id,'site_id'=>$siteA->id,'title'=>'Alpha Guide','slug'=>'guide','excerpt'=>'Alpha only','status'=>'published','body'=>[],'published_at'=>now()]);
        ContentItem::create(['workspace_id'=>$workspaceB->id,'site_id'=>$siteB->id,'title'=>'Beta Guide','slug'=>'guide','excerpt'=>'Beta only','status'=>'published','body'=>[],'published_at'=>now()]);

        $this->get('/s/'.$workspaceA->slug.'/main')->assertOk()->assertSee('Alpha Guide')->assertDontSee('Beta Guide');
        $this->get('/s/'.$workspaceB->slug.'/main')->assertOk()->assertSee('Beta Guide')->assertDontSee('Alpha Guide');
    }

    public function test_custom_domain_home_and_sitemap_resolve_to_the_correct_site(): void
    {
        [, $workspace, $site] = $this->tenant('domain-owner','domain@example.test');
        $site->update(['domain'=>'alpha.example.com']);
        ContentItem::create(['workspace_id'=>$workspace->id,'site_id'=>$site->id,'title'=>'Domain Guide','slug'=>'domain-guide','status'=>'published','body'=>[],'published_at'=>now()]);

        $this->withHeader('Host','alpha.example.com')->get('/')->assertOk()->assertSee('Domain Guide');
        $this->withHeader('Host','alpha.example.com')->get('/sitemap.xml')->assertOk()->assertSee('domain-guide');
        $this->withHeader('Host','alpha.example.com')->get('/domain-guide')->assertOk()->assertSee('Domain Guide');
    }

    public function test_expired_trial_without_subscription_cannot_consume_ai(): void
    {
        [, $workspace] = $this->tenant('expired','expired@example.test');
        $workspace->update(['trial_ends_at'=>now()->subDay()]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('trial has ended');
        app(PlanService::class)->assertCanUse($workspace->fresh(),'ai_generations');
    }

    private function tenant(string $slug, string $email): array
    {
        $user = User::create(['name'=>ucfirst($slug),'email'=>$email,'password'=>'StrongPass123!']);
        $workspace = Workspace::create(['owner_id'=>$user->id,'name'=>ucfirst($slug).' Workspace','slug'=>$slug,'status'=>'active','plan'=>'starter','trial_ends_at'=>now()->addDays(14),'billing_email'=>$email]);
        Membership::create(['workspace_id'=>$workspace->id,'user_id'=>$user->id,'role'=>'owner','accepted_at'=>now()]);
        $site = Site::create(['workspace_id'=>$workspace->id,'name'=>ucfirst($slug).' Site','slug'=>'main','status'=>'active','autonomy_level'=>2,'language'=>'en','timezone'=>'UTC','settings'=>[]]);
        return [$user,$workspace,$site];
    }
}
