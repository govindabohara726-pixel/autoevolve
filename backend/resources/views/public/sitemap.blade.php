@php
$customDomain = $site->domain && strtolower(request()->getHost()) === strtolower($site->domain);
$homeUrl = $customDomain ? url('/') : route('public.site',['workspace'=>$site->workspace->slug,'site'=>$site->slug]);
@endphp
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc>{{ $homeUrl }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
@foreach($items as $item)@php($articleUrl=$customDomain ? url('/'.$item->slug) : route('public.article',['workspace'=>$site->workspace->slug,'site'=>$site->slug,'slug'=>$item->slug]))<url><loc>{{ $articleUrl }}</loc><lastmod>{{ $item->updated_at?->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>@endforeach
</urlset>