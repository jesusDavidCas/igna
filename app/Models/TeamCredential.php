<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_member_id',
        'title',
        'credential_type',
        'institution',
        'issued_at',
        'document_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'preview_page_count',
        'is_public',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'preview_page_count' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(TeamCredentialView::class);
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf' || str_ends_with(strtolower($this->original_name), '.pdf');
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    public function hasRenderablePreview(): bool
    {
        return $this->isImage() && $this->preview_page_count > 0;
    }
}
