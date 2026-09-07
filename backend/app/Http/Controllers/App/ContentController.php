<?php
namespace App\Http\Controllers\App;
use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\ContentVersion;
use App\Services\AuditService;
use App\Services\ContentEngine;
use App\Services\PlanService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
class ContentController extends Controller {
    public function index(Request $request, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); $site=$ctx->site($request); $items=ContentItem::where('workspace_id',$workspace->id)->where('site_id',$site->id)->withCount(['revisions as pending_revisions_count'=>fn($q)=>$q->where('status','pending')])->latest('updated_at')->paginate(25); return view('app.content-index',compact('workspace','site','items')); }
    public function generate(Request $request, WorkspaceContext $ctx, PlanService $plans, ContentEngine $engine, AuditService $audit){
        $workspace=$ctx->workspace($request); $site=$ctx->site($request); $data=$request->validate(['topic'=>['required','string','max:240'],'type'=>['required',Rule::in(['guide','comparison','alternatives','best-of','how-to','decision'])]]);
        try{$plans->assertCanUse($workspace,'ai_generations'); $item=$engine->generate($data['topic'],$data['type'],$site); $plans->record($workspace,'ai_generations',1,$site,$request->user(),['content_id'=>$item->id]); $audit->log($request,$workspace,'content.generated',$item,$data);}catch(RuntimeException $e){return back()->withErrors(['topic'=>$e->getMessage()])->withInput();}
        return redirect()->route('app.content.edit',$item)->with('success','AI draft generated.');
    }
    public function edit(Request $request, ContentItem $content, WorkspaceContext $ctx){ $workspace=$ctx->workspace($request); $this->guard($content,$workspace->id); return view('app.content-edit',['workspace'=>$workspace,'site'=>$content->site,'item'=>$content,'versions'=>$content->versions()->latest()->limit(15)->get(),'revisions'=>$content->revisions()->latest()->limit(10)->get()]); }
    public function update(Request $request, ContentItem $content, WorkspaceContext $ctx, AuditService $audit){
        $workspace=$ctx->workspace($request); $this->guard($content,$workspace->id);
        $data=$request->validate(['title'=>['required','string','max:255'],'slug'=>['required','string','max:255',Rule::unique('content_items','slug')->where(fn($q)=>$q->where('site_id',$content->site_id))->ignore($content->id)],'excerpt'=>['nullable','string'],'primary_keyword'=>['nullable','string','max:255'],'search_intent'=>['nullable','string','max:100'],'meta_title'=>['nullable','string','max:255'],'meta_description'=>['nullable','string'],'status'=>['required',Rule::in(['draft','review','published','archived'])],'body_json'=>['required','json']]);
        ContentVersion::create(['content_item_id'=>$content->id,'snapshot'=>$content->toArray(),'reason'=>'Manual workspace edit','created_by'=>'user:'.$request->user()->id]); $content->fill(collect($data)->except('body_json')->all()); $content->body=json_decode($data['body_json'],true); if($content->status==='published')$content->published_at??=now(); $content->save(); $audit->log($request,$workspace,'content.updated',$content); return back()->with('success','Content saved.');
    }
    public function improve(Request $request, ContentItem $content, WorkspaceContext $ctx, PlanService $plans, ContentEngine $engine, AuditService $audit){
        $workspace=$ctx->workspace($request); $this->guard($content,$workspace->id); $data=$request->validate(['reason'=>['required','string','max:500']]);
        try{$plans->assertCanUse($workspace,'ai_improvements'); $engine->improve($content,$data['reason'],false); $plans->record($workspace,'ai_improvements',1,$content->site,$request->user(),['content_id'=>$content->id]); $audit->log($request,$workspace,'content.revision_created',$content,['reason'=>$data['reason']]);}catch(RuntimeException $e){return back()->withErrors(['reason'=>$e->getMessage()]);}
        return back()->with('success','AI improvement proposed. Live content is unchanged until you approve it.');
    }
    public function approveRevision(Request $request, ContentRevision $revision, WorkspaceContext $ctx, ContentEngine $engine, AuditService $audit){
        $workspace=$ctx->workspace($request); $this->guard($revision->content,$workspace->id); $item=$engine->applyRevision($revision,'user:'.$request->user()->id); $audit->log($request,$workspace,'content.revision_approved',$item,['revision_id'=>$revision->id]); return back()->with('success','AI revision approved and applied.');
    }
    public function rejectRevision(Request $request, ContentRevision $revision, WorkspaceContext $ctx, ContentEngine $engine, AuditService $audit){
        $workspace=$ctx->workspace($request); $this->guard($revision->content,$workspace->id); $engine->rejectRevision($revision,'user:'.$request->user()->id); $audit->log($request,$workspace,'content.revision_rejected',$revision->content,['revision_id'=>$revision->id]); return back()->with('success','AI revision rejected. Live content was not changed.');
    }
    public function publish(Request $request, ContentItem $content, WorkspaceContext $ctx, AuditService $audit){ $workspace=$ctx->workspace($request); $this->guard($content,$workspace->id); $content->update(['status'=>'published','published_at'=>$content->published_at?:now()]); $audit->log($request,$workspace,'content.published',$content); return back()->with('success','Content published.'); }
    public function destroy(Request $request, ContentItem $content, WorkspaceContext $ctx, AuditService $audit){ $workspace=$ctx->workspace($request); $this->guard($content,$workspace->id); $audit->log($request,$workspace,'content.deleted',$content,['title'=>$content->title]); $content->delete(); return redirect()->route('app.content.index')->with('success','Content deleted.'); }
    private function guard(ContentItem $content,string $workspaceId): void { abort_unless($content->workspace_id===$workspaceId,404); }
}
