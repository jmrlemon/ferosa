<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $minimumAppointmentAt = Carbon::now()->addHours(24)->format('Y-m-d H:i:s');

        return [
            'service_type_id' => ['required', 'exists:service_types,id'],
            'service_name' => ['nullable', 'string', 'max:255'], // legacy/compat
            'appointment_at' => [
                'required',
                'date',
                'after_or_equal:'.$minimumAppointmentAt,
                $this->withinDispatchSlot(...),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The booking form only offers Appointment::SLOT_TIMES, but that was a
     * browser-side constraint alone: a posted 03:17 appointment was accepted and
     * scheduled a crew for the middle of the night. The times are checked here
     * too, for the same reason prices and quantities are recalculated server
     * side rather than trusted from the form.
     */
    private function withinDispatchSlot(string $attribute, mixed $value, \Closure $fail): void
    {
        try {
            $time = Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return; // The `date` rule already reports an unparseable value.
        }

        if (! in_array($time, Appointment::SLOT_TIMES, true)) {
            $fail('Please choose one of the available visit times: '.implode(', ', Appointment::SLOT_TIMES).'.');
        }
    }

    public function messages(): array
    {
        return [
            'appointment_at.after_or_equal' => 'Appointments must be scheduled at least 24 hours in advance.',
        ];
    }
}
