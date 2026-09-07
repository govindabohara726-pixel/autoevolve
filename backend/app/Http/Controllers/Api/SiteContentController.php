<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\Site;
use App\Models\Workspace;
use Illuminate\Http\Request;
class SiteContentController extends Controller {
    private function site(string $workspaceSlug,string $siteSlug): Site { $workspace=Workspace::where('slug',$workspaceSlug)->where('status','active')->firstOrFail(); return Site::where('workspace_id',$workspace->id)->where('slug',$siteSlug)->where('status','active')->firstOrFail(); }
    public function showSite(string $workspaceSlug,string $siteSlug){ $site=$this->site($workspaceSlug,$siteSlug); return response()->json(['id'=>$site->id,'name'=>$site->name,'slug'=>$site->slug,'domain'=>$site->domain,'language'=>$site->language,'settings'=>$site->settings]); }
    public function index(Request $request,string $workspaceSlug,string $siteSlug){ $site=$this->site($workspaceSlug,$siteSlug); $limit=max(1,min(100,(int)$request->integer('limit',20))); return ContentItem::where('site_id',$site->id)->where('status','published')->latest('published_at')->limit($limit)->get(['id','title','slug','excerpt','primary_keyword','meta_title','meta_description','published_at','updated_at']); }
    public function show(string $workspaceSlug,string $siteSlug,string $slug){ $site=$this->site($workspaceSlug,$siteSlug); return ContentItem::where('site_id',$site->id)->where('slug',$slug)->where('status','published')->firstOrFail(); }
}
