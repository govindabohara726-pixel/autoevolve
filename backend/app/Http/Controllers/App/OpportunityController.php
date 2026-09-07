<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Services\EvolutionEngine;
use App\Services\PlanService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use RuntimeException;
class OpportunityController extends Controller {
    public function index(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); $site=$ctx->site($request); $items=Opportunity::where('workspace_id',$workspace->id)->where('site_id',$site->id)->orderByDesc('priority')->latest()->paginate(30); return view('app.opportunities',compact('workspace','site','items')); }
    public function discover(Request $request, WorkspaceContext $ctx, EvolutionEngine $engine, PlanService $plans){ $workspace=$ctx->workspace($request); $site=$ctx->site($request); try{$plans->assertCanUse($workspace,'ai_generations'); $count=$engine->discoverOpportunities($site); $plans->record($workspace,'ai_generations',1,$site,$request->user(),['operation'=>'opportunity_discovery']);}catch(RuntimeException $e){return back()->withErrors(['opportunities'=>$e->getMessage()]);} return back()->with('success',"Discovered {$count} new opportunities."); }
    public function dismiss(Request $request, Opportunity $opportunity, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); abort_unless($opportunity->workspace_id===$workspace->id,404); $opportunity->update(['status'=>'dismissed']); return back()->with('success','Opportunity dismissed.'); }
}
