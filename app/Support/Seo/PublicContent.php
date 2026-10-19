<?php

namespace App\Support\Seo;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicContent
{
    /** @return Builder<BlogPost> */
    public function publishedPostsQuery(): Builder
    {
        return BlogPost::query()
            ->where('status', BlogPostStatus::PUBLISHED->value)
            ->whereNotNull('published_at')
            ->whereNotIn('slug', config('igna.seo.excluded_blog_slugs', []));
    }

    /** @return Collection<int, BlogPost> */
    public function publishedPosts(): Collection
    {
        return $this->publishedPostsQuery()
            ->latest('published_at')
            ->get();
    }

    /** @return Builder<BlogPost> */
    public function englishPublishedPostsQuery(): Builder
    {
        return $this->publishedPostsQuery()
            ->whereIn('slug', $this->approvedEnglishBlogSlugs());
    }

    /** @return Collection<int, BlogPost> */
    public function englishPublishedPosts(): Collection
    {
        return $this->englishPublishedPostsQuery()
            ->latest('published_at')
            ->get()
            ->filter(fn (BlogPost $post): bool => $this->isEnglishBlogPostPublishable($post))
            ->values();
    }

    public function englishBlogArchiveIsPublishable(): bool
    {
        return $this->englishPublishedPosts()->isNotEmpty();
    }

    public function englishHomepageIsPublishable(): bool
    {
        // Team profiles have no persisted bilingual primary-content fields or approval state.
        // Do not publish an English homepage until that data model is reviewed and approved.
        return false;
    }

    public function isEnglishBlogPostPublishable(mixed $post): bool
    {
        if (! $post instanceof BlogPost) {
            return false;
        }

        return in_array($post->slug, $this->approvedEnglishBlogSlugs(), true)
            && $post->hasCompleteEnglishContent()
            && $this->publishedPostsQuery()->whereKey($post->getKey())->exists();
    }

    public function isEnglishTeamMemberPublishable(mixed $member): bool
    {
        // There is no safe English primary-content source for a TeamMember yet.
        return false;
    }

    /** @return Collection<int, TeamMember> */
    public function teamMembers(): Collection
    {
        return TeamMember::query()
            ->where('is_active', true)
            ->with('publicCredentials')
            ->orderBy('sort_order')
            ->get();
    }

    /** @return Collection<int, Service> */
    public function services(): Collection
    {
        return Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function stripHtmlToText(?string $html): string
    {
        $html = preg_replace('/<\s*br\s*\/?>/i', "\n", (string) $html) ?? '';
        $html = preg_replace('/<\/(p|h2|h3|li|blockquote)>/i', "\n", $html) ?? '';

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function markdownSafe(?string $value): string
    {
        return trim(Str::of((string) $value)->replace(["\r\n", "\r"], "\n")->squish()->toString());
    }

    /** @return array<int, string> */
    private function approvedEnglishBlogSlugs(): array
    {
        $slugs = config('igna.seo.english_publication.blog_slugs', []);

        if (! is_array($slugs)) {
            return [];
        }

        return collect($slugs)
            ->filter(fn (mixed $slug): bool => is_string($slug) && $slug !== '')
            ->values()
            ->all();
    }
}
