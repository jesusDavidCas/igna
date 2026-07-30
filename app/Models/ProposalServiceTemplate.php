<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProposalServiceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'service_number',
        'name_es',
        'name_en',
        'landing_title_es',
        'landing_title_en',
        'landing_description_es',
        'landing_description_en',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'service_number' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalServiceTemplateItem::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('service_number')->orderBy('id');
    }

    public function localizedName(): string
    {
        if (app()->getLocale() === 'en') {
            return $this->name_en ?: $this->name_es;
        }

        return $this->name_es ?: $this->name_en;
    }
}
