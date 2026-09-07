<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\ContentVersion;
use App\Services\ContentEngine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function index() { return view('admin.content.index', ['items'=>ContentItem::latest('updated_at')->paginate(30)]); }
    public function edit(ContentItem $content) { return view('admin.content.edit', ['item'=>$content,'versions'=>$content->versions()->latest()->limit(15)->get()]); }

    public function generate(Request $request, ContentEngine $engine)
    {
        $data = $request->validate(['topic'=>['required','string','max:240'],'type'=>['required',Rule::in(['guide','comparison','alternatives','best-of','how-to','decision'])]]);
        $item = $engine->generate($data['topic'], $data['type']);
        return redirect()->route('admin.content.edit',$item)->with('success','AI draft generated. Review it before publishing.');
    }

    public function update(Request $request, ContentItem $content)
    {
        $data = $request->validate([
            'title'=>['required','string','max:255'], 'slug'=>['required','string','max:255',Rule::unique('content_items','slug')->ignore($content->id)],
            'excerpt'=>['nullable','string'], 'primary_keyword'=>['nullable','string','max:255'], 'search_intent'=>['nullable','string','max:100'],
            'meta_title'=>['nullable','string','max:255'], 'meta_description'=>['nullable','string'], 'status'=>['required',Rule::in(['draft','review','published','archived'])],
            'body_json'=>['required','json'],
        ]);
        ContentVersion::create(['content_item_id'=>$content->id,'snapshot'=>$content->toArray(),'reason'=>'Manual admin edit','created_by'=>'admin']);
        $content->fill(collect($data)->except('body_json')->all());
        $content->body = json_decode($data['body_json'], true);
        if ($content->status === 'published') $content->published_at ??= now();
        $content->save();
        return back()->with('success','Content saved and previous version preserved.');
    }

    public function improve(Request $request, ContentItem $content, ContentEngine $engine)
    {
        $data = $request->validate(['reason'=>['required','string','max:500']]);
        $engine->improve($content, $data['reason'], false);
        return back()->with('success','AI improvement created. Previous version was preserved.');
    }

    public function publish(ContentItem $content)
    {
        $content->update(['status'=>'published','published_at'=>$content->published_at ?: now()]);
        return back()->with('success','Content published.');
    }

    public function destroy(ContentItem $content)
    {
        $content->delete();
        return redirect()->route('admin.content.index')->with('success','Content deleted.');
    }
}
