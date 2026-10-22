<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'role',
        'short_description',
        'bio',
        'expertise',
        'photo_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'bio' => 'array',
            'expertise' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (self $member): void {
            if (! $member->wasChanged(['name', 'role', 'short_description', 'bio', 'expertise'])) {
                return;
            }

            $member->englishTranslation()->update([
                'stale_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(TeamCredential::class)->orderBy('sort_order')->latest();
    }

    public function publicCredentials(): HasMany
    {
        return $this->credentials()->where('is_public', true);
    }

    public function englishTranslation(): HasOne
    {
        return $this->hasOne(TeamMemberTranslation::class)->where('locale', 'en');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(TeamMemberTranslation::class);
    }

    public function spanishSourceFingerprint(): string
    {
        return hash('sha256', json_encode([
            'name' => $this->name,
            'role' => $this->role,
            'short_description' => $this->short_description,
            'bio' => $this->bio ?? [],
            'expertise' => $this->expertise ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path || ! $this->slug) {
            return null;
        }

        return route('team.photo', [
            'teamMember' => $this->slug,
            'v' => $this->photoVersion(),
        ]);
    }

    public function photoVersion(): string
    {
        return substr(sha1($this->getKey().'|'.$this->photo_path.'|'.$this->updated_at?->timestamp), 0, 12);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name));

        $initials = collect($parts ?: [])
            ->filter()
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->join('');

        return $initials !== '' ? $initials : 'IS';
    }
}
