<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkProjectDeletionAllMatchingPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'selection_context' => ['required', 'string', 'size:40'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('selection_context')) {
                    return;
                }

                $context = session()->get('bulk_ticket_delete.selection_contexts.'.$this->input('selection_context'));

                if (! is_array($context)
                    || ($context['user_id'] ?? null) !== $this->user()?->id
                    || ($context['expires_at'] ?? 0) < now()->getTimestamp()
                    || ($context['scope'] ?? null) !== 'all_matching'
                    || ! is_array($context['ticket_ids'] ?? null)
                    || $context['ticket_ids'] === []) {
                    $validator->errors()->add('selection_context', __('site.bulk_delete_invalid_selection'));
                }
            },
        ];
    }

    /** @return array<int, int> */
    public function ticketIds(): array
    {
        $context = session()->get('bulk_ticket_delete.selection_contexts.'.$this->validated('selection_context'));

        return collect($context['ticket_ids'] ?? [])
            ->filter(fn (mixed $id): bool => is_int($id) || ctype_digit((string) $id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
