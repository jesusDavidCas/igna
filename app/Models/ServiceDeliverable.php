<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceDeliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'name_en',
        'name_es',
        'description',
        'description_en',
        'description_es',
        'sort_order',
        'is_active',
        'is_client_visible_by_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_client_visible_by_default' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function ticketDeliverables(): HasMany
    {
        return $this->hasMany(TicketDeliverable::class);
    }

    public function localizedName(): string
    {
        $field = app()->getLocale() === 'es' ? $this->name_es : $this->name_en;

        return filled($field) ? $field : $this->name;
    }

    public function localizedDescription(): ?string
    {
        $field = app()->getLocale() === 'es' ? $this->description_es : $this->description_en;

        return filled($field) ? $field : $this->description;
    }
}
