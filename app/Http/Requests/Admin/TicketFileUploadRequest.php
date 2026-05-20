<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TicketFileUploadRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'delivery_type' => $this->input('delivery_type', 'internal'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'ticket_deliverable_id' => ['nullable', 'exists:ticket_deliverables,id'],
            'deliverable_type' => ['nullable', 'string', 'max:120'],
            'delivery_type' => ['required', 'in:internal,partial,final'],
            'is_client_visible' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,zip,dwg,dxf'],
        ];
    }
}
