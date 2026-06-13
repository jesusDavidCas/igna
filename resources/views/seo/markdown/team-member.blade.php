# {{ $member->name }}

{{ $member->role }}

{{ $member->short_description }}

@foreach (($member->bio ?? []) as $paragraph)
{{ $paragraph }}

@endforeach

## Expertise

@foreach (($member->expertise ?? []) as $item)
- {{ $item }}
@endforeach

Canonical profile: {{ $seo->canonicalUrl('/team/'.$member->slug) }}
