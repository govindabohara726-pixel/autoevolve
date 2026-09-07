<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\PlanService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class SiteController extends Controller {
    public function index(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); $site=$ctx->site($request,false); return view('app.sites',['workspace'=>$workspace,'sites'=>$workspace->sites()->latest()->get(),'activeSite'=>$site,'site'=>$site]); }
    public function store(Request $request, WorkspaceContext $ctx, PlanService $plans, AuditService $audit){
        $workspace=$ctx->workspace($request); $data=$request->validate(['name'=>['required','string','max:120'],'domain'=>['nullable','string','max:190','unique:sites,domain']]);
        $limit=$plans->limit($workspace,'sites'); if($limit>0 && $workspace->sites()->count()>=$limit) return back()->withErrors(['name'=>'Your plan has reached its site limit.']);
        $base=Str::slug($data['name'])?:'site'; $slug=$base; $i=2; while(Site::where('workspace_id',$workspace->id)->where('slug',$slug)->exists()) $slug=$base.'-'.$i++;
        $site=Site::create(['workspace_id'=>$workspace->id,'name'=>$data['name'],'slug'=>$slug,'domain'=>$data['domain']?:null,'autonomy_level'=>2,'settings'=>['stale_after_days'=>30,'max_actions_per_run'=>5,'auto_publish_low_risk'=>false]]);
        $request->session()->put('site_id',$site->id); $audit->log($request,$workspace,'site.created',$site); return redirect()->route('app.sites')->with('success','Site created.');
    }
    public function switch(Request $request, Site $site, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); abort_unless($site->workspace_id===$workspace->id,404); $request->session()->put('site_id',$site->id); return back()->with('success','Active site changed to '.$site->name.'.'); }
    public function update(Request $request, Site $site, WorkspaceContext $ctx, AuditService $audit){
        $workspace=$ctx->workspace($request); abort_unless($site->workspace_id===$workspace->id,404);
        $data=$request->validate(['name'=>['required','string','max:120'],'domain'=>['nullable','string','max:190',Rule::unique('sites','domain')->ignore($site->id)],'language'=>['required','string','max:12'],'timezone'=>['required','string','max:100'],'autonomy_level'=>['required','integer','between:1,4']]);
        $site->update($data); $audit->log($request,$workspace,'site.updated',$site,$data); return back()->with('success','Site settings updated.');
    }
}
