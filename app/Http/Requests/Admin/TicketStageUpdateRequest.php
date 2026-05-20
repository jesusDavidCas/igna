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
            'service_stage_id' => ['nullable', 'required_without:stage_event_id', 'exists:service_stages,id'],
            'stage_event_id' => ['nullable', 'exists:ticket_stage_events,id'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
