<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc>{{ route('public.site',['site'=>$site->slug]) }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
@foreach($items as $item)<url><loc>{{ route('public.article',['site'=>$site->slug,'slug'=>$item->slug]) }}</loc><lastmod>{{ $item->updated_at?->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>@endforeach
</urlset>