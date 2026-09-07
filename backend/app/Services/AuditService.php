<?php
namespace App\Services;
use App\Models\AuditLog;
use App\Models\Workspace;
use Illuminate\Http\Request;
class AuditService {
    public function log(Request $request, ?Workspace $workspace, string $action, mixed $target=null, array $meta=[]): void {
        AuditLog::create(['workspace_id'=>$workspace?->id,'user_id'=>$request->user()?->id,'action'=>$action,'target_type'=>$target ? class_basename($target) : null,'target_id'=>$target?->getKey(),'meta'=>$meta ?: null,'ip_address'=>$request->ip(),'user_agent'=>mb_substr((string)$request->userAgent(),0,1000)]);
    }
}
