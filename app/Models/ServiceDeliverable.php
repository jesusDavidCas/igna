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
        $field = app()->getLocale() === 'es'
            ? ($this->isUsableTranslation($this->name_en, $this->name_es) ? $this->name_es : null)
            : $this->name_en;

        return filled($field) ? $field : $this->name;
    }

    public function localizedDescription(): ?string
    {
        $field = app()->getLocale() === 'es'
            ? ($this->isUsableTranslation($this->description_en, $this->description_es) ? $this->description_es : null)
            : $this->description_en;

        return filled($field) ? $field : $this->description;
    }

    private function isUsableTranslation(?string $source, ?string $target): bool
    {
        $source = mb_strtolower(trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $source, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));
        $target = mb_strtolower(trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $target, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));

        return $target !== '' && $target !== $source;
    }
}
