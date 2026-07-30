<?php

namespace App\Http\Requests\Admin;

use App\Services\Services\ServiceDeliverableNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serviceId = $this->route('service')?->id;
        $serviceTypes = collect(config('igna.service_types'))->flatMap(fn (array $types): array => $types)->keys()->all();
        $serviceScopes = array_keys(config('igna.service_scopes'));

        return [
            'name' => ['nullable', 'string', 'max:180'],
            'name_en' => ['nullable', 'string', 'max:180', 'required_without_all:name_es,name'],
            'name_es' => ['nullable', 'string', 'max:180', 'required_without_all:name_en,name'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('services', 'code')->ignore($serviceId),
            ],
            'business_line' => ['required', 'in:digital,engineering'],
            'service_type' => ['required', 'string', 'max:60', Rule::in($serviceTypes)],
            'service_scope' => ['required', 'string', 'max:60', Rule::in($serviceScopes)],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'description_es' => ['nullable', 'string'],
            'deliverables' => ['nullable'],
            'deliverables.*.en' => ['nullable', 'string', 'max:500'],
            'deliverables.*.es' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge([
                'code' => Str::upper($this->string('code')->trim()->toString()),
            ]);
        }

        $this->merge([
            'deliverables' => app(ServiceDeliverableNormalizer::class)->rows($this->input('deliverables')),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $businessLine = $this->input('business_line');
                $serviceType = $this->input('service_type');
                $validTypes = array_keys(config("igna.service_types.{$businessLine}", []));

                if ($businessLine && $serviceType && ! in_array($serviceType, $validTypes, true)) {
                    $validator->errors()->add('service_type', __('validation.in'));
                }
            },
        ];
    }
}
