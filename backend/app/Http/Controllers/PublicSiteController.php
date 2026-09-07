<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Site;
use App\Models\Workspace;
use Illuminate\Http\Request;

class PublicSiteController extends Controller
{
    public function index(Workspace $workspace, Site $site)
    {
        $this->guardSite($workspace, $site);
        $items = $site->content()->where('status', 'published')->orderByDesc('published_at')->paginate(20);
        return view('public.site-home', compact('site', 'items'));
    }

    public function show(Workspace $workspace, Site $site, string $slug)
    {
        $this->guardSite($workspace, $site);
        $item = ContentItem::where('site_id', $site->id)->where('status', 'published')->where('slug', $slug)->firstOrFail();
        return view('public.article', compact('site', 'item'));
    }

    public function customDomainArticle(Request $request, string $slug)
    {
        $site = $this->siteForHost($request->getHost());
        $item = ContentItem::where('site_id', $site->id)->where('status', 'published')->where('slug', $slug)->firstOrFail();
        return view('public.article', compact('site', 'item'));
    }

    public function sitemap(Workspace $workspace, Site $site)
    {
        $this->guardSite($workspace, $site);
        $items = $site->content()->where('status', 'published')->orderByDesc('updated_at')->get(['slug','updated_at']);
        return response()->view('public.sitemap', compact('site', 'items'))->header('Content-Type', 'application/xml');
    }

    public function customDomainSitemap(Request $request)
    {
        $site = Site::where('domain', strtolower($request->getHost()))->where('status','active')->first();
        if ($site) {
            $items = $site->content()->where('status','published')->orderByDesc('updated_at')->get(['slug','updated_at']);
            return response()->view('public.sitemap', compact('site','items'))->header('Content-Type','application/xml');
        }

        $base = rtrim(config('app.url'), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .'<url><loc>'.e($base).'/</loc></url>'
            .'<url><loc>'.e($base).'/privacy</loc></url>'
            .'<url><loc>'.e($base).'/terms</loc></url>'
            .'</urlset>';
        return response($xml,200,['Content-Type'=>'application/xml']);
    }

    public function robots(Request $request)
    {
        $host = strtolower($request->getHost());
        $site = Site::where('domain',$host)->where('status','active')->first();
        $sitemap = $site ? $request->getSchemeAndHttpHost().'/sitemap.xml' : rtrim(config('app.url'),'/').'/sitemap.xml';
        return response("User-agent: *\nAllow: /\nSitemap: {$sitemap}\n",200,['Content-Type'=>'text/plain']);
    }

    private function guardSite(Workspace $workspace, Site $site): void
    {
        abort_unless($site->workspace_id === $workspace->id && $site->status === 'active', 404);
    }

    private function siteForHost(string $host): Site
    {
        return Site::where('domain', strtolower($host))->where('status', 'active')->firstOrFail();
    }
}
