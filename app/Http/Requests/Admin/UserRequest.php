<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $passwordRules = $userId ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'];

        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'preferred_language' => ['required', 'in:es,en'],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => ['nullable', 'boolean'],
            'password' => $passwordRules,
        ];
    }
}
