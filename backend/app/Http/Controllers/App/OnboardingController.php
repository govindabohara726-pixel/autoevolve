<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Site;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class OnboardingController extends Controller {
    public function show(Request $request){ if($request->user()->memberships()->exists()) return redirect()->route('app.dashboard'); return view('app.onboarding'); }
    public function store(Request $request){
        $data=$request->validate(['name'=>['required','string','max:120']]); $base=Str::slug($data['name'])?:'workspace'; $slug=$base; $i=2; while(Workspace::where('slug',$slug)->exists()) $slug=$base.'-'.$i++;
        $workspace=Workspace::create(['owner_id'=>$request->user()->id,'name'=>$data['name'],'slug'=>$slug,'plan'=>config('plans.default'),'trial_ends_at'=>now()->addDays(14),'billing_email'=>$request->user()->email]);
        Membership::create(['workspace_id'=>$workspace->id,'user_id'=>$request->user()->id,'role'=>'owner','accepted_at'=>now()]);
        Site::create(['workspace_id'=>$workspace->id,'name'=>$data['name'].' Site','slug'=>'main','autonomy_level'=>2,'settings'=>['stale_after_days'=>30,'max_actions_per_run'=>5,'auto_publish_low_risk'=>false]]);
        $request->session()->put('workspace_id',$workspace->id); return redirect()->route('app.dashboard');
    }
}
