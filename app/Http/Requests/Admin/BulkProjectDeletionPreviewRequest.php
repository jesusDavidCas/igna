<?php

namespace App\Http\Requests\Admin;

use App\Services\Deletion\DeleteProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkProjectDeletionPreviewRequest extends FormRequest
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
            'ticket_ids' => ['required', 'array', 'min:1', 'max:50'],
            'ticket_ids.*' => ['bail', 'required', 'integer', 'distinct', 'exists:tickets,id'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['selection_context', 'ticket_ids', 'ticket_ids.*'])) {
                    return;
                }

                $ids = collect($this->input('ticket_ids'))
                    ->map(fn (mixed $id): int => (int) $id)
                    ->values();

                if (! collect($this->input('ticket_ids'))->every(fn (mixed $id): bool => ctype_digit((string) $id))) {
                    $validator->errors()->add('ticket_ids', __('site.bulk_delete_invalid_selection'));

                    return;
                }
                $context = session()->get('bulk_ticket_delete.selection_contexts.'.$this->input('selection_context'));

                if (! is_array($context)
                    || ($context['user_id'] ?? null) !== $this->user()?->id
                    || ($context['expires_at'] ?? 0) < now()->getTimestamp()
                    || ! $ids->every(fn (int $id): bool => in_array($id, $context['ticket_ids'] ?? [], true))) {
                    $validator->errors()->add('ticket_ids', __('site.bulk_delete_invalid_selection'));

                    return;
                }

                $protected = app(DeleteProject::class)->bulkProtectedTickets($ids);

                if ($protected->isNotEmpty()) {
                    $validator->errors()->add('ticket_ids', __('site.bulk_delete_requires_individual_review', [
                        'projects' => $protected->pluck('ticket_code')->implode(', '),
                    ]));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ticket_ids.required' => __('site.bulk_delete_empty_selection'),
            'ticket_ids.min' => __('site.bulk_delete_empty_selection'),
            'ticket_ids.max' => __('site.bulk_delete_batch_too_large'),
            'ticket_ids.*.distinct' => __('site.bulk_delete_duplicate_selection'),
            'ticket_ids.*.integer' => __('site.bulk_delete_invalid_selection'),
            'ticket_ids.*.exists' => __('site.bulk_delete_invalid_selection'),
        ];
    }
}
