<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urls as $entry)<url><loc>{{ $entry['loc'] }}</loc><lastmod>{{ $entry['lastmod'] }}</lastmod><changefreq>{{ $entry['frequency'] }}</changefreq></url>@endforeach
</urlset>
