<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;

class SaasController extends Controller
{
    public function index()
    {
        $workspaces = Workspace::with('owner')->withCount(['sites','memberships'])->latest()->paginate(30);
        $stats = [
            'workspaces' => Workspace::count(),
            'users' => User::count(),
            'sites' => Site::count(),
            'active_subscriptions' => Subscription::whereIn('status',['active','trialing'])->count(),
            'usage_this_month' => UsageEvent::where('occurred_at','>=',now()->startOfMonth())->sum('quantity'),
            'trialing_workspaces' => Workspace::where('trial_ends_at','>',now())->count(),
        ];
        return view('admin.saas', compact('workspaces','stats'));
    }

    public function show(Workspace $workspace)
    {
        $workspace->load(['owner','memberships.user','sites','subscriptions']);
        $usage = UsageEvent::where('workspace_id',$workspace->id)
            ->where('occurred_at','>=',now()->startOfMonth())
            ->selectRaw('meter, SUM(quantity) as total')->groupBy('meter')->pluck('total','meter');
        return view('admin.workspace', compact('workspace','usage'));
    }

    public function toggle(Request $request, Workspace $workspace)
    {
        $workspace->update(['status'=>$workspace->status === 'active' ? 'suspended' : 'active']);
        return back()->with('success','Workspace is now '.$workspace->status.'.');
    }
}
