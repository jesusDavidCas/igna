# {{ $post->localizedTitle() }}

Published: {{ optional($post->published_at)->format('Y-m-d') }}

{{ $post->localizedSummary() }}

{!! $bodyText !!}

Canonical article: {{ $seo->canonicalUrl('/blog/'.$post->slug) }}
