<?php

namespace App\Models;

use App\Enums\TranslationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'locale', 'title', 'summary', 'body_html', 'seo_title', 'seo_description',
        'status', 'source_fingerprint', 'approved_source_fingerprint', 'stale_at',
        'approved_by_user_id', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'stale_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function hasCompleteEnglishContent(): bool
    {
        return $this->locale === 'en' && filled($this->title) && filled($this->summary) && filled($this->body_html);
    }

    public function isApprovedFor(string $fingerprint): bool
    {
        return $this->status === TranslationStatus::APPROVED
            && $this->stale_at === null
            && $this->approved_by_user_id !== null
            && $this->approved_at !== null
            && filled($this->approved_source_fingerprint)
            && hash_equals($fingerprint, $this->approved_source_fingerprint)
            && $this->hasCompleteEnglishContent();
    }
}
