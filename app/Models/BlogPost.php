<?php

namespace App\Models;

use App\Enums\BlogPostStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
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
        return $this->localizedDemoValue('body_html', $this->body_html);
    }

    private function localizedDemoValue(string $field, string $fallback): string
    {
        $key = "demo.blog.{$this->slug}.{$field}";
        $value = __($key);

        return $value === $key ? $fallback : $value;
    }
}
