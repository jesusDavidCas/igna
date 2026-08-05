<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
            'brand_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'brand_favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'restore_brand_favicon' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $favicon = $this->file('brand_favicon');

            if (! $favicon) {
                return;
            }

            $path = $favicon->getRealPath();
            $size = $path ? @getimagesize($path) : false;
            $mimeType = $favicon->getMimeType();
            $extension = strtolower($favicon->getClientOriginalExtension());

            if (! $size || ! in_array($size['mime'] ?? null, ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'], true)) {
                $validator->errors()->add('brand_favicon', __('site.form_brand_favicon_invalid'));

                return;
            }

            if ($extension === 'png' && $mimeType !== 'image/png') {
                $validator->errors()->add('brand_favicon', __('site.form_brand_favicon_invalid'));

                return;
            }

            if ($extension === 'ico' && ! in_array($mimeType, ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'], true)) {
                $validator->errors()->add('brand_favicon', __('site.form_brand_favicon_invalid'));

                return;
            }

            [$width, $height] = $size;
            $largerSide = max($width, $height);
            $smallerSide = min($width, $height);

            if ($largerSide > 1024 || $smallerSide < 16 || ($smallerSide / $largerSide) < 0.85) {
                $validator->errors()->add('brand_favicon', __('site.form_brand_favicon_invalid'));
            }
        });
    }
}
