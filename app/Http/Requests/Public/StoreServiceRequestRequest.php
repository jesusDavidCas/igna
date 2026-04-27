<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'project_description' => ['required', 'string', 'max:5000'],
            'target_date' => ['nullable', 'date'],
            'initial_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,zip,dwg,dxf'],
        ];
    }
}
