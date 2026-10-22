<?php

namespace App\Models;

use App\Enums\TranslationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMemberTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'locale', 'role', 'short_description', 'bio', 'expertise', 'seo_title', 'seo_description',
        'status', 'source_fingerprint', 'approved_source_fingerprint', 'stale_at',
        'approved_by_user_id', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'bio' => 'array',
            'expertise' => 'array',
            'status' => TranslationStatus::class,
            'stale_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function hasCompleteEnglishContent(): bool
    {
        return $this->locale === 'en'
            && filled($this->role)
            && filled($this->short_description)
            && is_array($this->bio) && count($this->bio) > 0
            && is_array($this->expertise) && count($this->expertise) > 0;
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
