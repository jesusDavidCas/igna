<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $service = $this->route('service');
        $stageId = $this->route('stage')?->id;

        return [
            'name' => ['required', 'string', 'max:180'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('service_stages', 'code')
                    ->where(fn ($query) => $query->where('service_id', $service->id))
                    ->ignore($stageId),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'is_client_visible' => ['nullable', 'boolean'],
        ];
    }
}
