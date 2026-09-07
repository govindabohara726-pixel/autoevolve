<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\UsageEvent;
use Illuminate\Http\Request;
class AccountController extends Controller { public function show(Request $request){ $workspace=$request->attributes->get('workspace'); return response()->json(['workspace'=>$workspace?->only(['id','name','slug','plan','status','trial_ends_at']),'sites'=>$workspace?->sites()->get(['id','name','slug','domain','status','autonomy_level']),'usage'=>UsageEvent::where('workspace_id',$workspace?->id)->where('occurred_at','>=',now()->startOfMonth())->selectRaw('meter, sum(quantity) as quantity')->groupBy('meter')->pluck('quantity','meter')]); } }
