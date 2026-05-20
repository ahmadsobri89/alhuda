<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'       => ['required', 'exists:patients,id'],
            'doctor_name'      => ['required', 'string', 'max:255'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'duration_minutes' => ['required', 'integer', 'in:15,30,45,60'],
            'type'             => ['required', 'in:new,follow_up,annual_checkup,procedure,antenatal,teleconsult'],
            'reason'           => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
