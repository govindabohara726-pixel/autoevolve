<?php
namespace App\Services;
use App\Models\Site;
use App\Models\Workspace;
use Illuminate\Http\Request;
use RuntimeException;
class WorkspaceContext {
    public function workspace(Request $request): Workspace {
        $workspace=$request->attributes->get('workspace');
        if(!$workspace instanceof Workspace) throw new RuntimeException('Workspace context is unavailable.');
        return $workspace;
    }
    public function site(Request $request, bool $required=true): ?Site {
        $workspace=$this->workspace($request);
        $siteId=$request->session()->get('site_id');
        $site=$siteId ? Site::where('workspace_id',$workspace->id)->find($siteId) : null;
        $site ??= Site::where('workspace_id',$workspace->id)->where('status','active')->oldest()->first();
        if($site) $request->session()->put('site_id',$site->id);
        if(!$site && $required) throw new RuntimeException('Create a site before using this feature.');
        return $site;
    }
}
