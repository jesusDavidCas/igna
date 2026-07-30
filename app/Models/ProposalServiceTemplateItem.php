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
        if (app()->getLocale() === 'es') {
            return $this->isUsableTranslation($this->description_en, $this->description_es)
                ? $this->description_es
                : ($this->description_en ?: $this->description_es);
        }

        return $this->description_en ?: $this->description_es;
    }

    private function isUsableTranslation(?string $source, ?string $target): bool
    {
        $source = mb_strtolower(trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $source, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));
        $target = mb_strtolower(trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $target, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));

        return $target !== '' && $target !== $source;
    }
}
