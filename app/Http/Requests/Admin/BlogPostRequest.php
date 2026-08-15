<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'header_image' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp', 'max:4096'],
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasFile('header_image')) {
                    return;
                }

                $path = $this->file('header_image')?->getRealPath();
                $contents = $path ? @file_get_contents($path) : false;
                $image = is_string($contents) ? @getimagesizefromstring($contents) : false;
                $mimeType = is_array($image) ? ($image['mime'] ?? null) : null;

                if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $validator->errors()->add('header_image', __('validation.image', ['attribute' => 'header image']));
                }
            },
        ];
    }
}
