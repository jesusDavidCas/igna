<?php

namespace App\Http\Requests\Public;

use App\Models\Service;
use App\Services\Services\PublicServiceTaxonomy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'project_name' => ['required', 'string', 'max:180'],
            'project_location' => ['nullable', 'string', 'max:180'],
            'preferred_language' => ['required', 'in:es,en'],
            'service_id' => ['required'],
            'project_description' => ['required', 'string', 'max:5000'],
            'target_date' => ['nullable', 'date'],
            'initial_file' => [
                'nullable',
                'file',
                'max:2048',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $rawValue = $this->input('service_id');

                if (is_array($rawValue)) {
                    $validator->errors()->add('service_id', __('validation.exists'));

                    return;
                }

                $value = (string) $rawValue;

                if ($value === PublicServiceTaxonomy::OTHER) {
                    return;
                }

                if (in_array($value, app(PublicServiceTaxonomy::class)->codes(), true)) {
                    $validator->errors()->add('service_id', __('validation.exists'));

                    return;
                }

                if (! ctype_digit($value) || ! Service::query()->whereKey((int) $value)->where('is_active', true)->exists()) {
                    $validator->errors()->add('service_id', __('validation.exists'));
                }
            },
        ];
    }
}
