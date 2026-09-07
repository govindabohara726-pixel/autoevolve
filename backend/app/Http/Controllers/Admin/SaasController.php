<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Models\AuditLog;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'pending_ai' => AiAction::where('status','pending_approval')->count(),
        ];
        return view('admin.saas', compact('workspaces','stats'));
    }

    public function users(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $users = User::withCount('memberships')
            ->when($search, fn($q) => $q->where(fn($x) => $x->where('name','like','%'.$search.'%')->orWhere('email','like','%'.$search.'%')))
            ->latest()->paginate(40)->withQueryString();
        return view('admin.users', compact('users','search'));
    }

    public function sites(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $status = $request->query('status');
        $sites = Site::with('workspace')->withCount('content')
            ->when($search, fn($q) => $q->where(fn($x) => $x->where('name','like','%'.$search.'%')->orWhere('domain','like','%'.$search.'%')->orWhereHas('workspace', fn($w) => $w->where('name','like','%'.$search.'%'))))
            ->when(in_array($status,['active','paused'],true), fn($q) => $q->where('status',$status))
            ->latest()->paginate(40)->withQueryString();
        return view('admin.sites', compact('sites','search','status'));
    }

    public function subscriptions(Request $request)
    {
        $status = $request->query('status');
        $plan = $request->query('plan');
        $subscriptions = Subscription::with('workspace')
            ->when($status, fn($q) => $q->where('status',$status))
            ->when($plan, fn($q) => $q->where('plan',$plan))
            ->latest()->paginate(40)->withQueryString();
        return view('admin.subscriptions', compact('subscriptions','status','plan'));
    }

    public function usage()
    {
        $start = now()->startOfMonth();
        $summary = UsageEvent::where('occurred_at','>=',$start)
            ->selectRaw('meter, SUM(quantity) as total')->groupBy('meter')->orderByDesc('total')->get();
        $topWorkspaces = UsageEvent::join('workspaces','usage_events.workspace_id','=','workspaces.id')
            ->where('usage_events.occurred_at','>=',$start)
            ->selectRaw('workspaces.id, workspaces.name, SUM(usage_events.quantity) as total')
            ->groupBy('workspaces.id','workspaces.name')->orderByDesc('total')->limit(25)->get();
        $recent = UsageEvent::with(['workspace','site','user'])->latest('occurred_at')->paginate(50);
        return view('admin.usage', compact('summary','topWorkspaces','recent'));
    }

    public function activity(Request $request)
    {
        $kind = $request->query('kind','ai');
        $aiActions = AiAction::with(['workspace','site','content'])->latest()->paginate(50, ['*'], 'ai_page');
        $auditLogs = AuditLog::with(['workspace','user'])->latest()->paginate(50, ['*'], 'audit_page');
        return view('admin.activity', compact('kind','aiActions','auditLogs'));
    }

    public function system()
    {
        $database = false;
        try { DB::select('select 1'); $database = true; } catch (\Throwable) {}
        $checks = [
            'Database connection' => $database,
            'APP_DEBUG disabled' => !config('app.debug'),
            'AI provider configured' => filled(config('services.ai.key')),
            'Stripe secret configured' => filled(config('services.stripe.secret')),
            'Stripe webhook configured' => filled(config('services.stripe.webhook_secret')),
            'All Stripe prices configured' => collect(config('plans.plans',[]))->every(fn($plan) => filled($plan['price_id'] ?? null)),
            'Storage writable' => is_writable(storage_path()),
            'Bootstrap cache writable' => is_writable(base_path('bootstrap/cache')),
            'Installer locked' => is_file(storage_path('app/autoevolve-installed')),
        ];
        $runtime = [
            'PHP' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Environment' => app()->environment(),
            'Queue pending' => $database ? DB::table('jobs')->count() : 0,
            'Failed jobs' => $database ? DB::table('failed_jobs')->count() : 0,
            'Last site evolution' => Site::max('last_evolved_at') ?: 'Never',
            'Mailer' => config('mail.default'),
        ];
        return view('admin.system', compact('checks','runtime'));
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
        AuditLog::create(['workspace_id'=>$workspace->id,'user_id'=>$request->user()?->id,'action'=>'admin.workspace_status_changed','target_type'=>Workspace::class,'target_id'=>$workspace->id,'meta'=>['status'=>$workspace->status],'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent()]);
        return back()->with('success','Workspace is now '.$workspace->status.'.');
    }

    public function updatePlan(Request $request, Workspace $workspace)
    {
        $data = $request->validate(['plan'=>['required',Rule::in(array_keys(config('plans.plans',[])))]]);
        $before = $workspace->plan;
        $workspace->update(['plan'=>$data['plan']]);
        AuditLog::create(['workspace_id'=>$workspace->id,'user_id'=>$request->user()?->id,'action'=>'admin.plan_changed','target_type'=>Workspace::class,'target_id'=>$workspace->id,'meta'=>['from'=>$before,'to'=>$data['plan']],'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent()]);
        return back()->with('success','Workspace plan changed to '.ucfirst($data['plan']).'. Stripe billing is not modified by this support override.');
    }

    public function toggleSite(Request $request, Site $site)
    {
        $site->update(['status'=>$site->status === 'active' ? 'paused' : 'active']);
        AuditLog::create(['workspace_id'=>$site->workspace_id,'user_id'=>$request->user()?->id,'action'=>'admin.site_status_changed','target_type'=>Site::class,'target_id'=>$site->id,'meta'=>['status'=>$site->status],'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent()]);
        return back()->with('success','Site is now '.$site->status.'.');
    }
}
