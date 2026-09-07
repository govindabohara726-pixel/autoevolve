<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Site;
use Illuminate\Http\Request;

class PublicSiteController extends Controller
{
    public function index(Site $site)
    {
        abort_unless($site->status === 'active', 404);
        $items = $site->content()->where('status', 'published')->orderByDesc('published_at')->paginate(20);
        return view('public.site-home', compact('site', 'items'));
    }

    public function show(Site $site, string $slug)
    {
        abort_unless($site->status === 'active', 404);
        $item = ContentItem::where('site_id', $site->id)->where('status', 'published')->where('slug', $slug)->firstOrFail();
        return view('public.article', compact('site', 'item'));
    }

    public function customDomainArticle(Request $request, string $slug)
    {
        $site = Site::where('domain', $request->getHost())->where('status', 'active')->firstOrFail();
        $item = ContentItem::where('site_id', $site->id)->where('status', 'published')->where('slug', $slug)->firstOrFail();
        return view('public.article', compact('site', 'item'));
    }

    public function sitemap(Site $site)
    {
        abort_unless($site->status === 'active', 404);
        $items = $site->content()->where('status', 'published')->orderByDesc('updated_at')->get(['slug','updated_at']);
        return response()->view('public.sitemap', compact('site', 'items'))->header('Content-Type', 'application/xml');
    }
}
