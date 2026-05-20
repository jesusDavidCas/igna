<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TeamCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'institution' => ['nullable', 'string', 'max:180'],
            'issued_at' => ['nullable', 'date'],
            'document' => ['required', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png,webp'],
            'is_public' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
