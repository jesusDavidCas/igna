<?php

namespace App\Http\Requests\Admin;

use App\Models\ProposalServiceTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProposalServiceTemplateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item): bool => is_array($item))
            ->filter(fn (array $item): bool => collect($item)->contains(fn ($value): bool => filled($value)))
            ->map(fn (array $item): array => [
                'item_code' => filled($item['item_code'] ?? null) ? Str::upper(trim((string) $item['item_code'])) : null,
                'description_en' => trim((string) ($item['description_en'] ?? '')),
                'description_es' => trim((string) ($item['description_es'] ?? '')),
                'unit' => filled($item['unit'] ?? null) ? trim((string) $item['unit']) : null,
                'quantity' => $this->numericValue($item['quantity'] ?? null, integer: true),
                'unit_value' => $this->numericValue($item['unit_value'] ?? null),
            ])
            ->values()
            ->all();

        $nameEn = trim((string) $this->input('name_en', ''));
        $nameEs = trim((string) $this->input('name_es', ''));

        $this->merge([
            'code' => $this->filled('code') ? Str::upper($this->string('code')->trim()->toString()) : null,
            'name_en' => $nameEn,
            'name_es' => $nameEs,
            'landing_title_en' => $this->filled('landing_title_en') ? trim((string) $this->input('landing_title_en')) : $nameEn,
            'landing_title_es' => $this->filled('landing_title_es') ? trim((string) $this->input('landing_title_es')) : $nameEs,
            'is_active' => $this->boolean('is_active'),
            'items' => $items,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        $templateId = $this->route('proposalTemplate')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique(ProposalServiceTemplate::class, 'code')->ignore($templateId),
            ],
            'service_number' => ['required', 'integer', 'min:1', 'max:9999'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_es' => ['required', 'string', 'max:255'],
            'landing_title_en' => ['required', 'string', 'max:255'],
            'landing_title_es' => ['required', 'string', 'max:255'],
            'landing_description_en' => ['nullable', 'string', 'max:10000'],
            'landing_description_es' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1', 'max:80'],
            'items.*.item_code' => ['nullable', 'string', 'max:40'],
            'items.*.description_en' => ['required', 'string', 'max:1200'],
            'items.*.description_es' => ['required', 'string', 'max:1200'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'items.*.unit_value' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => __('site.proposal_template_code'),
            'service_number' => __('site.proposal_template_number'),
            'name_en' => __('site.proposal_template_title_en'),
            'name_es' => __('site.proposal_template_title_es'),
            'sort_order' => __('site.proposal_template_sort_order'),
            'is_active' => __('site.proposal_template_status'),
            'items' => __('site.proposal_template_items'),
            'items.*.item_code' => __('site.template_row_code'),
            'items.*.description_en' => __('site.template_row_en'),
            'items.*.description_es' => __('site.template_row_es'),
            'items.*.unit' => __('site.template_row_unit'),
            'items.*.quantity' => __('site.qty_abbr'),
            'items.*.unit_value' => __('site.unit_value_label'),
        ];
    }

    private function numericValue(mixed $value, bool $integer = false): string|int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.,-]/', '', (string) $value);

        if ($normalized === null || $normalized === '' || $normalized === '-' || ! preg_match('/\d/', $normalized)) {
            return null;
        }

        $normalized = str_replace(',', '', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        return $integer ? (int) $normalized : (float) $normalized;
    }
}
