<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\AuditService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Stripe\StripeClient;
use Stripe\Webhook;
use Throwable;
class BillingController extends Controller {
    public function index(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); return view('app.billing',['workspace'=>$workspace,'plans'=>config('plans.plans'),'subscription'=>$workspace->subscriptions()->latest()->first(),'stripeConfigured'=>filled(config('services.stripe.secret'))]); }
    public function checkout(Request $request, WorkspaceContext $ctx, AuditService $audit){
        $workspace=$ctx->workspace($request); $this->owner($request); $data=$request->validate(['plan'=>['required','string']]); $plan=config('plans.plans.'.$data['plan']); abort_unless($plan,404);
        if(blank(config('services.stripe.secret')) || blank($plan['price_id']??null)) return back()->withErrors(['billing'=>'Stripe or the selected Stripe price is not configured yet.']);
        $stripe=new StripeClient(config('services.stripe.secret'));
        if(!$workspace->stripe_customer_id){ $customer=$stripe->customers->create(['email'=>$workspace->billing_email ?: $request->user()->email,'name'=>$workspace->name,'metadata'=>['workspace_id'=>$workspace->id]]); $workspace->update(['stripe_customer_id'=>$customer->id]); }
        $session=$stripe->checkout->sessions->create(['customer'=>$workspace->stripe_customer_id,'mode'=>'subscription','line_items'=>[['price'=>$plan['price_id'],'quantity'=>1]],'success_url'=>route('app.billing').'?checkout=success','cancel_url'=>route('app.billing').'?checkout=cancelled','client_reference_id'=>$workspace->id,'metadata'=>['workspace_id'=>$workspace->id,'plan'=>$data['plan']],'subscription_data'=>['metadata'=>['workspace_id'=>$workspace->id,'plan'=>$data['plan']]],'allow_promotion_codes'=>true]);
        $audit->log($request,$workspace,'billing.checkout_started',$workspace,['plan'=>$data['plan']]); return redirect()->away($session->url);
    }
    public function portal(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); $this->owner($request); if(blank(config('services.stripe.secret')) || !$workspace->stripe_customer_id)return back()->withErrors(['billing'=>'No Stripe customer is connected yet.']); $session=(new StripeClient(config('services.stripe.secret')))->billingPortal->sessions->create(['customer'=>$workspace->stripe_customer_id,'return_url'=>route('app.billing')]); return redirect()->away($session->url); }
    public function webhook(Request $request){
        $secret=config('services.stripe.webhook_secret'); if(blank($secret))return response()->json(['error'=>'Stripe webhook secret missing'],503);
        try{$event=Webhook::constructEvent($request->getContent(),(string)$request->header('Stripe-Signature'),$secret);}catch(Throwable $e){return response()->json(['error'=>'Invalid webhook'],400);}
        $object=$event->data->object;
        if($event->type==='checkout.session.completed'){
            $workspaceId=$object->metadata->workspace_id ?? $object->client_reference_id ?? null; $workspace=$workspaceId?Workspace::find($workspaceId):null;
            if($workspace && isset($object->customer))$workspace->update(['stripe_customer_id'=>(string)$object->customer]);
            if($workspace && isset($object->subscription)){ try{$subscription=(new StripeClient(config('services.stripe.secret')))->subscriptions->retrieve((string)$object->subscription,[]); $this->syncSubscription($subscription,$workspace);}catch(Throwable){} }
        }
        if(in_array($event->type,['customer.subscription.created','customer.subscription.updated','customer.subscription.deleted'],true))$this->syncSubscription($object);
        return response()->json(['received'=>true]);
    }
    private function syncSubscription(mixed $object, ?Workspace $workspace=null): void {
        $workspaceId=$object->metadata->workspace_id ?? null; $workspace ??= $workspaceId?Workspace::find((string)$workspaceId):null; $workspace ??= isset($object->customer)?Workspace::where('stripe_customer_id',(string)$object->customer)->first():null; if(!$workspace)return;
        $priceId=$object->items->data[0]->price->id ?? null; $plan=$object->metadata->plan ?? $this->planForPrice($priceId) ?? $workspace->plan; $period=$object->current_period_end ?? ($object->items->data[0]->current_period_end ?? null);
        Subscription::updateOrCreate(['provider_subscription_id'=>(string)$object->id],['workspace_id'=>$workspace->id,'provider'=>'stripe','provider_price_id'=>$priceId,'plan'=>$plan,'status'=>(string)($object->status ?? 'inactive'),'current_period_end'=>$period?Carbon::createFromTimestamp((int)$period):null,'cancel_at_period_end'=>(bool)($object->cancel_at_period_end ?? false),'payload'=>json_decode(json_encode($object),true)]);
        if(in_array((string)($object->status ?? ''),['active','trialing'],true))$workspace->update(['plan'=>$plan,'status'=>'active']);
    }
    private function planForPrice(?string $priceId): ?string { foreach(config('plans.plans',[]) as $key=>$plan)if($priceId && ($plan['price_id']??null)===$priceId)return $key; return null; }
    private function owner(Request $request): void { abort_unless($request->attributes->get('membership')?->role==='owner',403); }
}
