<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkProjectDeletionConfirmRequest extends FormRequest
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
            'confirmation_token' => ['required', 'string', 'size:40'],
            'confirm_deletion' => ['required', 'accepted'],
            'typed_confirmation' => ['nullable', 'string', 'max:64'],
        ];
    }
}
