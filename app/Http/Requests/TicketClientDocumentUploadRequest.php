<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketClientDocumentUploadRequest extends FormRequest
{
    public const CATEGORIES = [
        'payment_receipt',
        'requested_document',
        'supporting_document',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'document' => [
                'required',
                'file',
                'max:2048',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
            ],
        ];
    }
}
