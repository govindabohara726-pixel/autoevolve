<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use Illuminate\Http\Request;
class ContentController extends Controller {
    public function index(Request $request){ $limit=max(1,min(100,(int)$request->integer('limit',20))); return response()->json(ContentItem::whereNull('workspace_id')->where('status','published')->orderByDesc('published_at')->limit($limit)->get(['id','title','slug','excerpt','primary_keyword','updated_at','published_at'])); }
    public function show(string $slug){ return response()->json(ContentItem::whereNull('workspace_id')->where('status','published')->where('slug',$slug)->firstOrFail()); }
    public function health(){ return response()->json(['ok'=>true,'service'=>'autoevolve-laravel-saas','database'=>true,'ai'=>filled(config('services.ai.key')),'stripe'=>filled(config('services.stripe.secret')),'saas'=>true,'time'=>now()->toIso8601String()]); }
}
