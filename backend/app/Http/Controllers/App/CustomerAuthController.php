<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Site;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class CustomerAuthController extends Controller {
    public function showLogin(){ return view('auth.customer-login'); }
    public function showRegister(){ return view('auth.register'); }
    public function login(Request $request){
        $credentials=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        if(!Auth::attempt($credentials,$request->boolean('remember'))) return back()->withErrors(['email'=>'Those credentials do not match our records.'])->onlyInput('email');
        $request->session()->regenerate();
        $membership=$request->user()->memberships()->whereNotNull('accepted_at')->first();
        if($membership) $request->session()->put('workspace_id',$membership->workspace_id);
        return redirect()->intended(route($membership?'app.dashboard':'app.onboarding'));
    }
    public function register(Request $request){
        $data=$request->validate(['name'=>['required','string','max:100'],'email'=>['required','email','max:190','unique:users,email'],'password'=>['required','string','min:8','confirmed'],'workspace_name'=>['required','string','max:120']]);
        [$user,$workspace]=DB::transaction(function() use($data){
            $user=User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>$data['password']]);
            $workspace=Workspace::create(['owner_id'=>$user->id,'name'=>$data['workspace_name'],'slug'=>$this->uniqueWorkspaceSlug($data['workspace_name']),'plan'=>config('plans.default'),'trial_ends_at'=>now()->addDays(14),'billing_email'=>$user->email]);
            Membership::create(['workspace_id'=>$workspace->id,'user_id'=>$user->id,'role'=>'owner','accepted_at'=>now()]);
            Site::create(['workspace_id'=>$workspace->id,'name'=>$data['workspace_name'].' Site','slug'=>'main','autonomy_level'=>2,'timezone'=>config('app.timezone'),'settings'=>['stale_after_days'=>30,'max_actions_per_run'=>5,'auto_publish_low_risk'=>false]]);
            return [$user,$workspace];
        });
        Auth::login($user); $request->session()->regenerate(); $request->session()->put('workspace_id',$workspace->id);
        return redirect()->route('app.dashboard')->with('success','Your AutoEvolve workspace is ready.');
    }
    public function logout(Request $request){ Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); }
    private function uniqueWorkspaceSlug(string $name): string { $base=Str::slug($name) ?: 'workspace'; $slug=$base; $i=2; while(Workspace::where('slug',$slug)->exists()) $slug=$base.'-'.$i++; return $slug; }
}
