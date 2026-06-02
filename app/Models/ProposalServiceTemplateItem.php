<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalServiceTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_service_template_id',
        'item_code',
        'description_es',
        'description_en',
        'unit',
        'quantity',
        'unit_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_value' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProposalServiceTemplate::class, 'proposal_service_template_id');
    }

    public function localizedDescription(): string
    {
        return app()->getLocale() === 'en'
            ? ($this->description_en ?: $this->description_es)
            : $this->description_es;
    }
}
