<?php

namespace App\Services\Seo;

use App\Enums\BlogPostStatus;
use App\Enums\TranslationStatus;
use App\Models\BlogPost;
use App\Models\BlogPostTranslation;
use App\Models\TeamMember;
use App\Models\TeamMemberTranslation;
use App\Models\User;
use App\Support\Html\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicTranslationWorkflow
{
    /** @param array<string, mixed> $attributes */
    public function saveBlogEnglish(BlogPost $post, array $attributes): BlogPostTranslation
    {
        return DB::transaction(function () use ($post, $attributes): BlogPostTranslation {
            $lockedPost = BlogPost::query()->whereKey($post->getKey())->lockForUpdate()->firstOrFail();
            $translation = BlogPostTranslation::query()
                ->where('blog_post_id', $lockedPost->getKey())
                ->where('locale', 'en')
                ->lockForUpdate()
                ->first() ?? (new BlogPostTranslation(['locale' => 'en']))->post()->associate($lockedPost);

            $translation->fill([
                'title' => $attributes['title'],
                'summary' => $attributes['summary'],
                'body_html' => app(HtmlSanitizer::class)->clean($attributes['body_html']),
                'seo_title' => $attributes['seo_title'] ?? null,
                'seo_description' => $attributes['seo_description'] ?? null,
                'status' => ! empty($attributes['submit_for_review']) ? TranslationStatus::IN_REVIEW : TranslationStatus::DRAFT,
                'source_fingerprint' => $lockedPost->spanishSourceFingerprint(),
                'approved_source_fingerprint' => null,
                'approved_by_user_id' => null,
                'approved_at' => null,
                'stale_at' => now(),
            ]);
            $translation->save();

            return $translation;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function saveTeamEnglish(TeamMember $member, array $attributes): TeamMemberTranslation
    {
        return DB::transaction(function () use ($member, $attributes): TeamMemberTranslation {
            $lockedMember = TeamMember::query()->whereKey($member->getKey())->lockForUpdate()->firstOrFail();
            $translation = TeamMemberTranslation::query()
                ->where('team_member_id', $lockedMember->getKey())
                ->where('locale', 'en')
                ->lockForUpdate()
                ->first() ?? (new TeamMemberTranslation(['locale' => 'en']))->member()->associate($lockedMember);

            $translation->fill([
                'role' => $attributes['role'],
                'short_description' => $attributes['short_description'],
                'bio' => $this->lines($attributes['bio']),
                'expertise' => $this->lines($attributes['expertise']),
                'seo_title' => $attributes['seo_title'] ?? null,
                'seo_description' => $attributes['seo_description'] ?? null,
                'status' => ! empty($attributes['submit_for_review']) ? TranslationStatus::IN_REVIEW : TranslationStatus::DRAFT,
                'source_fingerprint' => $lockedMember->spanishSourceFingerprint(),
                'approved_source_fingerprint' => null,
                'approved_by_user_id' => null,
                'approved_at' => null,
                'stale_at' => now(),
            ]);
            $translation->save();

            return $translation;
        });
    }

    public function approveBlogEnglish(BlogPost $post, User $reviewer): BlogPostTranslation
    {
        if (! $reviewer->is_active || ! $reviewer->isSuperAdmin()) {
            abort(403);
        }

        return DB::transaction(function () use ($post, $reviewer): BlogPostTranslation {
            $lockedPost = BlogPost::query()->whereKey($post->getKey())->lockForUpdate()->firstOrFail();
            $translation = BlogPostTranslation::query()
                ->where('blog_post_id', $lockedPost->getKey())
                ->where('locale', 'en')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPost->status !== BlogPostStatus::PUBLISHED || $lockedPost->published_at === null) {
                $this->rejectApproval('parent', 'The Spanish blog post must be published before approval.');
            }

            $this->approve($translation, $lockedPost->spanishSourceFingerprint(), $reviewer);

            return $translation;
        });
    }

    public function approveTeamEnglish(TeamMember $member, User $reviewer): TeamMemberTranslation
    {
        if (! $reviewer->is_active || ! $reviewer->isSuperAdmin()) {
            abort(403);
        }

        return DB::transaction(function () use ($member, $reviewer): TeamMemberTranslation {
            $lockedMember = TeamMember::query()->whereKey($member->getKey())->lockForUpdate()->firstOrFail();
            $translation = TeamMemberTranslation::query()
                ->where('team_member_id', $lockedMember->getKey())
                ->where('locale', 'en')
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedMember->is_active) {
                $this->rejectApproval('parent', 'The Spanish team profile must be active before approval.');
            }

            $this->approve($translation, $lockedMember->spanishSourceFingerprint(), $reviewer);

            return $translation;
        });
    }

    private function approve(BlogPostTranslation|TeamMemberTranslation $translation, string $fingerprint, User $reviewer): void
    {
        if ($translation->status !== TranslationStatus::IN_REVIEW) {
            $this->rejectApproval('status', 'The translation must be submitted for review.');
        }

        if (! $translation->hasCompleteEnglishContent()) {
            $this->rejectApproval('content', 'The English translation is incomplete.');
        }

        if (! filled($translation->source_fingerprint) || ! hash_equals($fingerprint, $translation->source_fingerprint)) {
            $this->rejectApproval('source', 'The Spanish source changed after this translation was saved.');
        }

        $translation->forceFill([
            'status' => TranslationStatus::APPROVED,
            'approved_source_fingerprint' => $fingerprint,
            'approved_by_user_id' => $reviewer->getKey(),
            'approved_at' => now(),
            'stale_at' => null,
        ])->save();
    }

    /** @return array<int, string> */
    private function lines(string $value): array
    {
        return collect(preg_split('/\R+/', $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function rejectApproval(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
