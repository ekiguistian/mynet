@php($social = json_decode($settings['social_media'] ?? '{}', true) ?: [])
@if (filled($settings['seo_description'] ?? null))<meta name="description" content="{{ $settings['seo_description'] }}">@endif
@if (filled($settings['seo_keywords'] ?? null))<meta name="keywords" content="{{ $settings['seo_keywords'] }}">@endif
@if (filled($settings['google_search_console_url'] ?? null))<meta name="google-site-verification" content="{{ $settings['google_search_console_url'] }}">@endif
<meta property="og:site_name" content="mynet">
<meta property="og:title" content="{{ $settings['seo_title'] ?? 'mynet' }}">
@if (filled($settings['seo_description'] ?? null))<meta property="og:description" content="{{ $settings['seo_description'] }}">@endif
@foreach ($social as $network => $url)
    @if (filled($url))<link rel="me" href="{{ $url }}" data-social="{{ $network }}">@endif
@endforeach
