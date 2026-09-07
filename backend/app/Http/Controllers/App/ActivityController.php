<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
class ActivityController extends Controller { public function __invoke(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); $logs=AuditLog::where('workspace_id',$workspace->id)->with('user')->latest()->paginate(40); return view('app.activity',compact('workspace','logs')); } }
