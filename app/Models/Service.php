<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'name_es',
        'slug',
        'code',
        'business_line',
        'service_type',
        'service_scope',
        'description',
        'description_en',
        'description_es',
        'deliverables_schema',
        'legacy_deliverables_schema',
        'deliverables_normalization_notes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'deliverables_schema' => 'array',
            'legacy_deliverables_schema' => 'array',
            'deliverables_normalization_notes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ServiceStage::class)->orderBy('sort_order');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ServiceDeliverable::class)->orderBy('sort_order');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function localizedName(): string
    {
        $field = app()->getLocale() === 'es' ? $this->name_es : $this->name_en;

        if (filled($field)) {
            return $field;
        }

        return __("services.catalog.{$this->code}.name") !== "services.catalog.{$this->code}.name"
            ? __("services.catalog.{$this->code}.name")
            : $this->name;
    }

    public function localizedDescription(): ?string
    {
        $field = app()->getLocale() === 'es' ? $this->description_es : $this->description_en;

        if (filled($field)) {
            return $field;
        }

        return __("services.catalog.{$this->code}.description") !== "services.catalog.{$this->code}.description"
            ? __("services.catalog.{$this->code}.description")
            : $this->description;
    }

    public function localizedDeliverables(): array
    {
        $deliverables = $this->relationLoaded('deliverables')
            ? $this->deliverables
            : $this->deliverables()->where('is_active', true)->orderBy('sort_order')->get();
        $locale = app()->getLocale();

        if ($deliverables->isNotEmpty() && ($locale !== 'es' || $deliverables->contains(fn (ServiceDeliverable $deliverable): bool => filled($deliverable->name_es)))) {
            return $deliverables
                ->map(fn (ServiceDeliverable $deliverable): string => $deliverable->localizedName())
                ->filter()
                ->values()
                ->all();
        }

        $translated = __("services.catalog.{$this->code}.deliverables");

        if (is_array($translated)) {
            return $translated;
        }

        return collect($this->deliverables_schema ?? [])
            ->map(fn (mixed $deliverable): string => is_array($deliverable)
                ? (string) ($deliverable[$locale] ?? $deliverable['en'] ?? $deliverable['es'] ?? '')
                : (string) $deliverable)
            ->filter()
            ->values()
            ->all();
    }
}
