<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TicketStageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_stage_id' => ['required', 'exists:service_stages,id'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
