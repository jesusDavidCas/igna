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
        'code',
        'description',
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
        return __("stages.{$this->code}") !== "stages.{$this->code}"
            ? __("stages.{$this->code}")
            : $this->name;
    }
}
