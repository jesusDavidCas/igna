<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'name_en',
        'name_es',
        'code',
        'description',
        'description_en',
        'description_es',
        'sort_order',
        'is_active',
        'is_client_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_client_visible' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function ticketStageEvents(): HasMany
    {
        return $this->hasMany(TicketStageEvent::class);
    }

    public function localizedName(): string
    {
        $field = app()->getLocale() === 'es' ? $this->name_es : $this->name_en;

        if (filled($field)) {
            return $field;
        }

        return __("stages.{$this->code}") !== "stages.{$this->code}"
            ? __("stages.{$this->code}")
            : $this->name;
    }

    public function localizedDescription(): ?string
    {
        $field = app()->getLocale() === 'es' ? $this->description_es : $this->description_en;

        return filled($field) ? $field : $this->description;
    }
}
