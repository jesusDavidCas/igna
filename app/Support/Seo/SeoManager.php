<?php

namespace App\Support\Seo;

use App\Models\BlogPost;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoManager
{
    public function canonicalBase(): string
    {
        return rtrim((string) config('igna.seo.canonical_url', config('app.url')), '/');
    }

    public function canonicalUrl(?string $path = null): string
    {
        $path = $path === null ? '/' : $path;
        $path = '/'.ltrim($path, '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $this->canonicalBase().$path;
    }

    public function currentCanonical(Request $request): string
    {
        return $this->canonicalUrl($request->path() === '/' ? '/' : $request->path());
    }

    public function socialImage(): string
    {
        return $this->canonicalUrl(config('igna.seo.social_image', '/social-card.svg'));
    }

    public function meta(array $overrides = []): array
    {
        $title = $overrides['title'] ?? config('igna.seo.default_title');
        $description = $overrides['description'] ?? __('site.meta_description');
        $canonical = $overrides['canonical'] ?? $this->currentCanonical(request());
        $image = $overrides['image'] ?? $this->socialImage();
        $type = $overrides['type'] ?? 'website';
        $robots = $overrides['robots'] ?? 'index, follow';
        $schema = $overrides['schema'] ?? [];

        return [
            'title' => $title,
            'description' => Str::limit(strip_tags((string) $description), 160, ''),
            'canonical' => $canonical,
            'robots' => $robots,
            'image' => $image,
            'type' => $type,
            'locale' => app()->getLocale() === 'en' ? 'en_US' : 'es_CO',
            'site_name' => 'IGNA Studio',
            'schema' => array_values(array_filter($schema)),
        ];
    }

    public function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            '@id' => $this->canonicalUrl('/#organization'),
            'name' => 'IGNA Studio',
            'url' => $this->canonicalUrl('/'),
            'logo' => $this->socialImage(),
            'description' => __('site.meta_description'),
            'areaServed' => 'Colombia',
            'knowsAbout' => [
                'Digital systems',
                'Project tracking platforms',
                'Water infrastructure engineering',
                'Aqueduct design',
                'Sanitary sewer design',
                'Stormwater management',
                'Water treatment projects',
            ],
        ];
    }

    public function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $this->canonicalUrl('/#website'),
            'name' => 'IGNA Studio',
            'url' => $this->canonicalUrl('/'),
            'publisher' => ['@id' => $this->canonicalUrl('/#organization')],
            'inLanguage' => app()->getLocale() === 'en' ? 'en' : 'es',
        ];
    }

    public function webpageSchema(string $name, string $description, ?string $path = null): array
    {
        $url = $path ? $this->canonicalUrl($path) : $this->currentCanonical(request());

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $name,
            'description' => Str::limit(strip_tags($description), 200, ''),
            'isPartOf' => ['@id' => $this->canonicalUrl('/#website')],
            'about' => ['@id' => $this->canonicalUrl('/#organization')],
            'inLanguage' => app()->getLocale() === 'en' ? 'en' : 'es',
        ];
    }

    public function personSchema(TeamMember $member): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => $this->canonicalUrl('/team/'.$member->slug.'#person'),
            'name' => $member->name,
            'url' => $this->canonicalUrl('/team/'.$member->slug),
            'jobTitle' => $member->role,
            'description' => $member->short_description,
            'worksFor' => ['@id' => $this->canonicalUrl('/#organization')],
            'knowsAbout' => array_values((array) $member->expertise),
        ];
    }

    public function blogPostingSchema(BlogPost $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => $this->canonicalUrl('/blog/'.$post->slug.'#blogposting'),
            'headline' => $post->localizedTitle(),
            'description' => $post->localizedSummary(),
            'url' => $this->canonicalUrl('/blog/'.$post->slug),
            'datePublished' => optional($post->published_at)->toAtomString(),
            'dateModified' => optional($post->updated_at)->toAtomString(),
            'publisher' => ['@id' => $this->canonicalUrl('/#organization')],
            'mainEntityOfPage' => ['@id' => $this->canonicalUrl('/blog/'.$post->slug.'#webpage')],
            'inLanguage' => app()->getLocale() === 'en' ? 'en' : 'es',
        ];
    }
}
