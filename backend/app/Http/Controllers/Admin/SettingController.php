<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index() { return view('admin.settings.index', ['settings'=>SiteSetting::current()]); }
    public function update(Request $request)
    {
        $data=$request->validate(['autonomy_level'=>['required','integer','between:1,4'],'stale_after_days'=>['required','integer','between:1,365'],'max_actions_per_run'=>['required','integer','between:1,50'],'site_name'=>['required','string','max:120'],'site_url'=>['nullable','url','max:255']]);
        $data['auto_publish_low_risk']=$request->boolean('auto_publish_low_risk');
        SiteSetting::current()->update($data);
        return back()->with('success','Settings saved.');
    }
}
