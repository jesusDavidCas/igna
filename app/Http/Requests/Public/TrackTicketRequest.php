<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class TrackTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_code' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:180'],
        ];
    }
}
