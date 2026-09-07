<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Models\ContentItem;
use App\Models\Opportunity;
use App\Services\PlanService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
class DashboardController extends Controller {
    public function __invoke(Request $request, WorkspaceContext $ctx, PlanService $plans){
        $workspace=$ctx->workspace($request); $site=$ctx->site($request,false);
        $content=ContentItem::where('workspace_id',$workspace->id)->when($site,fn($q)=>$q->where('site_id',$site->id));
        $actions=AiAction::where('workspace_id',$workspace->id)->when($site,fn($q)=>$q->where('site_id',$site->id));
        return view('app.dashboard',[
            'workspace'=>$workspace,'site'=>$site,'sites'=>$workspace->sites()->orderBy('name')->get(),
            'stats'=>['published'=>(clone $content)->where('status','published')->count(),'drafts'=>(clone $content)->whereIn('status',['draft','review'])->count(),'avg_health'=>(int)round((clone $content)->avg('health_score') ?: 0),'actions_this_month'=>(clone $actions)->where('created_at','>=',now()->startOfMonth())->count()],
            'recentActions'=>(clone $actions)->latest()->limit(8)->get(),
            'opportunities'=>Opportunity::where('workspace_id',$workspace->id)->when($site,fn($q)=>$q->where('site_id',$site->id))->where('status','new')->orderByDesc('priority')->limit(6)->get(),
            'plan'=>$plans->plan($workspace),'usage'=>['ai_generations'=>$plans->used($workspace,'ai_generations'),'ai_improvements'=>$plans->used($workspace,'ai_improvements'),'evolution_runs'=>$plans->used($workspace,'evolution_runs')],
            'subscription'=>$workspace->subscriptions()->latest()->first(),
        ]);
    }
}
