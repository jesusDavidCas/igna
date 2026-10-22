<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BlogPostTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->canAccessAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:1000'],
            'body_html' => ['required', 'string'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'submit_for_review' => ['nullable', 'boolean'],
        ];
    }
}
