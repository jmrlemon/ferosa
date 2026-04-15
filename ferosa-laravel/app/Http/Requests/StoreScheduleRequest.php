<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type_id' => ['required', 'exists:service_types,id'],
            'service_name' => ['nullable', 'string', 'max:255'], // legacy/compat
            'appointment_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
