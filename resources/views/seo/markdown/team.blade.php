# IGNA Studio Team

{{ __('site.team_intro') }}

@foreach ($teamMembers as $member)
## {{ $member->name }}

{{ $member->role }}

{{ $member->short_description }}

Profile: {{ $seo->canonicalUrl('/team/'.$member->slug) }}

Markdown: {{ $seo->canonicalUrl('/markdown/team/'.$member->slug.'.md') }}

@endforeach
