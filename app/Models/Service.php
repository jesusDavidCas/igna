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
        'slug',
        'code',
        'business_line',
        'service_type',
        'service_scope',
        'description',
        'deliverables_schema',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'deliverables_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ServiceStage::class)->orderBy('sort_order');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function localizedName(): string
    {
        return __("services.catalog.{$this->code}.name") !== "services.catalog.{$this->code}.name"
            ? __("services.catalog.{$this->code}.name")
            : $this->name;
    }

    public function localizedDescription(): ?string
    {
        return __("services.catalog.{$this->code}.description") !== "services.catalog.{$this->code}.description"
            ? __("services.catalog.{$this->code}.description")
            : $this->description;
    }

    public function localizedDeliverables(): array
    {
        $translated = __("services.catalog.{$this->code}.deliverables");

        return is_array($translated) ? $translated : ($this->deliverables_schema ?? []);
    }
}
