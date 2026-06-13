# IGNA Studio

Official site: {{ $seo->canonicalUrl('/') }}

IGNA Studio is a digital systems and water infrastructure support studio. The team helps clients organize complex technical needs into clear projects, practical digital tools, traceable workflows, and structured engineering deliverables.

## Main Areas

- Digital systems for project tracking, customer/request management, and lightweight operational platforms.
- Water infrastructure and civil engineering support for aqueduct, sanitary sewer, stormwater, hydrology, fire protection, drinking water treatment, and wastewater treatment projects.

## Public Pages

- Homepage: {{ $seo->canonicalUrl('/') }}
- Blog: {{ $seo->canonicalUrl('/blog') }}
- Markdown homepage mirror: {{ $seo->canonicalUrl('/markdown/home.md') }}
- Markdown services mirror: {{ $seo->canonicalUrl('/markdown/services.md') }}
- Markdown team mirror: {{ $seo->canonicalUrl('/markdown/team.md') }}

## Principal Services

@foreach ($services as $service)
- {{ $service->localizedName() }}: {{ $service->localizedDescription() }}
@endforeach

## Public Team Profiles

@foreach ($teamMembers as $member)
- {{ $member->name }} — {{ $member->role }}: {{ $seo->canonicalUrl('/team/'.$member->slug) }}; Markdown: {{ $seo->canonicalUrl('/markdown/team/'.$member->slug.'.md') }}
@endforeach

## Public Blog Resources

@forelse ($posts as $post)
- {{ $post->localizedTitle() }}: {{ $seo->canonicalUrl('/blog/'.$post->slug) }}; Markdown: {{ $seo->canonicalUrl('/markdown/blog/'.$post->slug.'.md') }}
@empty
- No public articles are currently available.
@endforelse

## Excluded Content

This file intentionally excludes admin pages, client portals, private proposals, credential files, temporary signed URLs, customer data, environment values, internal APIs, and unpublished content.
