<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TicketFileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'deliverable_type' => ['nullable', 'string', 'max:120'],
            'is_client_visible' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,zip,dwg,dxf'],
        ];
    }
}
