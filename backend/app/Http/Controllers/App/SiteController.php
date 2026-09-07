<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\AuditService;
use App\Services\PlanService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class SiteController extends Controller {
    public function index(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); $site=$ctx->site($request,false); return view('app.sites',['workspace'=>$workspace,'sites'=>$workspace->sites()->latest()->get(),'activeSite'=>$site,'site'=>$site]); }
    public function store(Request $request, WorkspaceContext $ctx, PlanService $plans, AuditService $audit){
        $workspace=$ctx->workspace($request); $data=$request->validate(['name'=>['required','string','max:120'],'domain'=>['nullable','string','max:253']]);
        $domain=$this->normalizeDomain($data['domain']??null); if($domain && !$this->validDomain($domain)) return back()->withErrors(['domain'=>'Enter a valid domain such as example.com.'])->withInput();
        if($domain && $this->reservedDomain($domain)) return back()->withErrors(['domain'=>'That hostname is reserved for the AutoEvolve platform.'])->withInput();
        if($domain && Site::where('domain',$domain)->exists()) return back()->withErrors(['domain'=>'That domain is already connected to another site.'])->withInput();
        $limit=$plans->limit($workspace,'sites'); if($limit>0 && $workspace->sites()->count()>=$limit) return back()->withErrors(['name'=>'Your plan has reached its site limit.']);
        $base=Str::slug($data['name'])?:'site'; $slug=$base; $i=2; while(Site::where('workspace_id',$workspace->id)->where('slug',$slug)->exists()) $slug=$base.'-'.$i++;
        $site=Site::create(['workspace_id'=>$workspace->id,'name'=>$data['name'],'slug'=>$slug,'domain'=>$domain,'autonomy_level'=>2,'settings'=>['stale_after_days'=>30,'max_actions_per_run'=>5,'auto_publish_low_risk'=>false]]);
        $request->session()->put('site_id',$site->id); $audit->log($request,$workspace,'site.created',$site); return redirect()->route('app.sites')->with('success','Site created.');
    }
    public function switch(Request $request, Site $site, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); abort_unless($site->workspace_id===$workspace->id,404); $request->session()->put('site_id',$site->id); return back()->with('success','Active site changed to '.$site->name.'.'); }
    public function update(Request $request, Site $site, WorkspaceContext $ctx, AuditService $audit){
        $workspace=$ctx->workspace($request); abort_unless($site->workspace_id===$workspace->id,404);
        $data=$request->validate(['name'=>['required','string','max:120'],'domain'=>['nullable','string','max:253'],'language'=>['required','string','max:12'],'timezone'=>['required','string','max:100'],'autonomy_level'=>['required','integer','between:1,4']]);
        $domain=$this->normalizeDomain($data['domain']??null); if($domain && !$this->validDomain($domain)) return back()->withErrors(['domain'=>'Enter a valid domain such as example.com.'])->withInput();
        if($domain && $this->reservedDomain($domain)) return back()->withErrors(['domain'=>'That hostname is reserved for the AutoEvolve platform.'])->withInput();
        if($domain && Site::where('domain',$domain)->where('id','<>',$site->id)->exists()) return back()->withErrors(['domain'=>'That domain is already connected to another site.'])->withInput();
        $data['domain']=$domain; $site->update($data); $audit->log($request,$workspace,'site.updated',$site,$data); return back()->with('success','Site settings updated.');
    }
    private function normalizeDomain(?string $value): ?string {
        $value=trim(strtolower((string)$value)); if($value==='') return null;
        if(str_contains($value,'://')) $value=(string)(parse_url($value,PHP_URL_HOST)?:'');
        else $value=explode('/', $value, 2)[0];
        $value=preg_replace('/:\d+$/','',$value) ?? $value;
        return rtrim($value,'.') ?: null;
    }
    private function validDomain(string $domain): bool { return (bool)preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',$domain); }
    private function reservedDomain(string $domain): bool {
        $hosts=array_filter([(string)parse_url((string)config('app.url'),PHP_URL_HOST),(string)parse_url((string)config('services.frontend_url'),PHP_URL_HOST)]);
        return in_array($domain,array_map('strtolower',$hosts),true);
    }
}
