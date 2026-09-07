<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Services\AuditService;
use App\Services\EvolutionEngine;
use App\Services\PlanService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use RuntimeException;
class AutomationController extends Controller {
    public function index(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); $site=$ctx->site($request); $actions=AiAction::where('workspace_id',$workspace->id)->where('site_id',$site->id)->latest()->paginate(30); return view('app.automation',compact('workspace','site','actions')); }
    public function run(Request $request, WorkspaceContext $ctx, PlanService $plans, EvolutionEngine $engine, AuditService $audit){ $workspace=$ctx->workspace($request); $site=$ctx->site($request); try{$plans->assertCanUse($workspace,'evolution_runs'); $result=$engine->run($site); $plans->record($workspace,'evolution_runs',1,$site,$request->user(),$result); $audit->log($request,$workspace,'automation.run',$site,$result);}catch(RuntimeException $e){return back()->withErrors(['automation'=>$e->getMessage()]);} return back()->with('success','Evolution cycle completed: '.count($result['improved']).' pages processed, '.$result['opportunities_found'].' opportunities found.'); }
}
