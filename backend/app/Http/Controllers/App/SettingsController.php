<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Membership;
use App\Models\User;
use App\Services\AuditService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class SettingsController extends Controller {
    public function index(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); return view('app.settings',['workspace'=>$workspace,'memberships'=>$workspace->memberships()->with('user')->get(),'apiKeys'=>ApiKey::where('workspace_id',$workspace->id)->latest()->get()]); }
    public function update(Request $request, WorkspaceContext $ctx, AuditService $audit){ $workspace=$ctx->workspace($request); $this->ownerOrAdmin($request); $data=$request->validate(['name'=>['required','string','max:120'],'billing_email'=>['nullable','email','max:190']]); $workspace->update($data); $audit->log($request,$workspace,'workspace.updated',$workspace,$data); return back()->with('success','Workspace updated.'); }
    public function addMember(Request $request, WorkspaceContext $ctx, AuditService $audit){ $workspace=$ctx->workspace($request); $this->ownerOrAdmin($request); $data=$request->validate(['email'=>['required','email'],'role'=>['required','in:admin,editor,viewer']]); $user=User::where('email',$data['email'])->first(); if(!$user)return back()->withErrors(['email'=>'That person must create an AutoEvolve account before you add them.']); Membership::updateOrCreate(['workspace_id'=>$workspace->id,'user_id'=>$user->id],['role'=>$data['role'],'accepted_at'=>now()]); $audit->log($request,$workspace,'member.added',$user,['role'=>$data['role']]); return back()->with('success','Team member added.'); }
    public function removeMember(Request $request, Membership $membership, WorkspaceContext $ctx, AuditService $audit){ $workspace=$ctx->workspace($request); $this->ownerOrAdmin($request); abort_unless($membership->workspace_id===$workspace->id,404); if($membership->role==='owner')return back()->withErrors(['member'=>'The workspace owner cannot be removed.']); $audit->log($request,$workspace,'member.removed',$membership->user,['role'=>$membership->role]); $membership->delete(); return back()->with('success','Team member removed.'); }
    public function createApiKey(Request $request, WorkspaceContext $ctx, AuditService $audit){ $workspace=$ctx->workspace($request); $this->ownerOrAdmin($request); $data=$request->validate(['name'=>['required','string','max:100']]); $plain='ae_'.Str::random(48); $key=ApiKey::create(['workspace_id'=>$workspace->id,'name'=>$data['name'],'token_hash'=>hash('sha256',$plain)]); $audit->log($request,$workspace,'api_key.created',$key); return back()->with('success','API key created. Copy it now; it will not be shown again.')->with('new_api_key',$plain); }
    public function revokeApiKey(Request $request, ApiKey $apiKey, WorkspaceContext $ctx, AuditService $audit){ $workspace=$ctx->workspace($request); $this->ownerOrAdmin($request); abort_unless($apiKey->workspace_id===$workspace->id,404); $apiKey->update(['revoked_at'=>now()]); $audit->log($request,$workspace,'api_key.revoked',$apiKey); return back()->with('success','API key revoked.'); }
    private function ownerOrAdmin(Request $request): void { abort_unless(in_array($request->attributes->get('membership')?->role,['owner','admin'],true),403); }
}
