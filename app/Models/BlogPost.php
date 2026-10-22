<?php

namespace App\Models;

use App\Enums\BlogPostStatus;
use App\Services\Blog\BlogHeaderImageManager;
use App\Support\Html\HtmlSanitizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'header_image_path',
        'body_html',
        'status',
        'published_at',
        'seo_keywords',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => BlogPostStatus::class,
            'published_at' => 'datetime',
            'seo_keywords' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (self $post): void {
            if (! $post->wasChanged(['title', 'summary', 'body_html', 'seo_keywords'])) {
                return;
            }

            $post->englishTranslation()->update([
                'stale_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function englishTranslation(): HasOne
    {
        return $this->hasOne(BlogPostTranslation::class)->where('locale', 'en');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BlogPostTranslation::class);
    }

    public function spanishSourceFingerprint(): string
    {
        return hash('sha256', json_encode([
            'title' => $this->title,
            'summary' => $this->summary,
            'body_html' => $this->body_html,
            'seo_keywords' => $this->seo_keywords ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function headerImageUrl(): ?string
    {
        if (! $this->header_image_path || ! app(BlogHeaderImageManager::class)->hasPublicFile($this)) {
            return null;
        }

        return route('blog.header-image', [
            'post' => $this,
            'v' => $this->headerImageVersion(),
        ]);
    }

    public function headerImageVersion(): string
    {
        return sha1($this->getKey().'|'.$this->header_image_path.'|'.$this->updated_at?->timestamp);
    }

    public function localizedTitle(): string
    {
        return $this->localizedDemoValue('title', $this->title);
    }

    public function localizedSummary(): string
    {
        return $this->localizedDemoValue('summary', $this->summary);
    }

    public function localizedBodyHtml(): string
    {
        return app(HtmlSanitizer::class)->clean(
            $this->localizedDemoValue('body_html', $this->body_html),
        );
    }

    public function hasCompleteEnglishContent(): bool
    {
        return $this->englishTranslation?->hasCompleteEnglishContent() ?? false;
    }

    public function englishTitle(): ?string
    {
        return $this->englishTranslation?->title;
    }

    public function englishSummary(): ?string
    {
        return $this->englishTranslation?->summary;
    }

    public function englishBodyHtml(): ?string
    {
        $body = $this->englishTranslation?->body_html;

        return $body === null ? null : app(HtmlSanitizer::class)->clean($body);
    }

    private function localizedDemoValue(string $field, string $fallback): string
    {
        return $this->localizedValue($field, app()->getLocale()) ?? $fallback;
    }

    private function localizedValue(string $field, string $locale): ?string
    {
        $key = "demo.blog.{$this->slug}.{$field}";
        $value = trans($key, [], $locale);

        return $value === $key || ! is_string($value) || ! filled($value) ? null : $value;
    }
}
