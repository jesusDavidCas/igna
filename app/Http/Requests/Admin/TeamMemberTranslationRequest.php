<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TeamMemberTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->canAccessAdmin();
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:180'],
            'short_description' => ['required', 'string', 'max:1000'],
            'bio' => ['required', 'string', 'max:5000'],
            'expertise' => ['required', 'string', 'max:3000'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'submit_for_review' => ['nullable', 'boolean'],
        ];
    }
}
