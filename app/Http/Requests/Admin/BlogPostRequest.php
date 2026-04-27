<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id;

        return [
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:1000'],
            'body_html' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'seo_keywords' => ['nullable', 'string', 'max:1000'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('blog_posts', 'slug')->ignore($postId)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->filled('slug') ? $this->input('slug') : $this->input('title');

        if ($slug) {
            $this->merge([
                'slug' => Str::slug($slug),
            ]);
        }
    }
}
