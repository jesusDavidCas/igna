<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProposalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn (array $item): bool => filled($item['description'] ?? null))
            ->map(fn (array $item): array => [
                ...$item,
                'quantity' => $this->numericValue($item['quantity'] ?? null, integer: true),
                'unit_value' => $this->numericValue($item['unit_value'] ?? null),
            ])
            ->values()
            ->all();

        $payments = collect($this->input('payment_schedule', []))
            ->filter(fn (array $payment): bool => filled($payment['percentage'] ?? null) || filled($payment['label'] ?? null) || filled($payment['notes'] ?? null))
            ->values()
            ->all();

        $this->merge(['items' => $items, 'payment_schedule' => $payments]);
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

        if ($integer && $this->looksLikeDecimalQuantity($normalized)) {
            return $normalized;
        }

        $normalized = str_replace(',', '', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        return $integer ? (int) $normalized : (float) $normalized;
    }

    private function looksLikeDecimalQuantity(string $value): bool
    {
        if (str_contains($value, '.')) {
            return true;
        }

        return str_contains($value, ',')
            && ! preg_match('/^-?\d{1,3}(,\d{3})+$/', $value);
    }

    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'client_user_id' => ['nullable', Rule::exists('users', 'id')->where('role', UserRole::CLIENT->value)],
            'prospect_name' => ['nullable', 'string', 'max:180'],
            'prospect_email' => ['nullable', 'email', 'max:180'],
            'prospect_phone' => ['nullable', 'string', 'max:80'],
            'signer_user_id' => ['nullable', Rule::exists('users', 'id')->whereIn('role', [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value])],
            'title' => ['required', 'string', 'max:180'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'scope' => ['nullable', 'string', 'max:10000'],
            'timeline_months' => ['required', 'integer', 'min:0', 'max:60'],
            'timeline_weeks' => ['required', 'integer', 'min:0', 'max:12'],
            'payment_schedule' => ['required', 'array', 'min:1', 'max:8'],
            'payment_schedule.*.label' => ['nullable', 'string', 'max:120'],
            'payment_schedule.*.percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'payment_schedule.*.notes' => ['nullable', 'string', 'max:240'],
            'status' => ['required', Rule::in(['draft', 'sent', 'approved', 'rejected'])],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['nullable', 'string', 'max:120'],
            'items.*.item_code' => ['nullable', 'string', 'max:40'],
            'items.*.description' => ['required', 'string', 'max:1200'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'items.*.unit_value' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'client_user_id' => __('site.client'),
            'prospect_name' => __('site.manual_client_name'),
            'prospect_email' => __('site.manual_client_email'),
            'prospect_phone' => __('site.manual_client_phone'),
            'signer_user_id' => __('site.proposal_signer'),
            'title' => __('site.form_title'),
            'subject' => __('site.subject'),
            'description' => __('site.proposal_description'),
            'scope' => __('site.proposal_scope'),
            'timeline_months' => __('site.timeline_months'),
            'timeline_weeks' => __('site.timeline_weeks'),
            'payment_schedule' => __('site.payment_schedule_and_totals'),
            'payment_schedule.*.label' => __('site.payment_label'),
            'payment_schedule.*.percentage' => __('site.percentage'),
            'payment_schedule.*.notes' => __('site.payment_notes'),
            'status' => __('site.form_status'),
            'tax_rate' => __('site.tax_rate'),
            'issued_at' => __('site.issued_at'),
            'valid_until' => __('site.valid_until'),
            'items' => __('site.cost_items'),
            'items.*.item_code' => __('site.item_code'),
            'items.*.description' => __('site.item_description'),
            'items.*.unit' => __('site.unit_abbr'),
            'items.*.quantity' => __('site.qty_abbr'),
            'items.*.unit_value' => __('site.unit_value_label'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $total = collect($this->input('payment_schedule', []))
                    ->sum(fn (array $payment): float => (float) ($payment['percentage'] ?? 0));

                if (abs($total - 100) > 0.01) {
                    $validator->errors()->add('payment_schedule', __('site.payment_schedule_total_error'));
                }

                if ((int) $this->input('timeline_months', 0) === 0 && (int) $this->input('timeline_weeks', 0) === 0) {
                    $validator->errors()->add('timeline_months', __('site.timeline_required_error'));
                }
            },
        ];
    }
}
