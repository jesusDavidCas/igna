<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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

    public function credentials(): HasMany
    {
        return $this->hasMany(TeamCredential::class)->orderBy('sort_order')->latest();
    }

    public function publicCredentials(): HasMany
    {
        return $this->credentials()->where('is_public', true);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
