<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class ResolveWorkspace {
    public function handle(Request $request, Closure $next): Response {
        $user=$request->user();
        $membershipQuery=$user->memberships()->with('workspace')->whereNotNull('accepted_at');
        $workspaceId=$request->session()->get('workspace_id');
        $membership=$workspaceId ? (clone $membershipQuery)->where('workspace_id',$workspaceId)->first() : null;
        $membership ??= $membershipQuery->first();
        if(!$membership || !$membership->workspace || $membership->workspace->status!=='active') return redirect()->route('app.onboarding');
        $request->session()->put('workspace_id',$membership->workspace_id);
        $request->attributes->set('workspace',$membership->workspace);
        $request->attributes->set('membership',$membership);
        view()->share('currentWorkspace',$membership->workspace);
        view()->share('currentMembership',$membership);
        return $next($request);
    }
}
