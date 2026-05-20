<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $patientId = $this->route('patient')?->id;

        return [
            'name'                    => ['required', 'string', 'max:255'],
            'ic_number'               => ['required', 'string', 'max:20', "unique:patients,ic_number,{$patientId}"],
            'date_of_birth'           => ['required', 'date', 'before:today'],
            'gender'                  => ['required', 'in:male,female'],
            'phone'                   => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'address'                 => ['nullable', 'string', 'max:500'],
            'postcode'                => ['nullable', 'string', 'max:10'],
            'city'                    => ['nullable', 'string', 'max:100'],
            'state'                   => ['nullable', 'string', 'max:100'],
            'blood_type'              => ['nullable', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-,Unknown'],
            'allergies'               => ['nullable', 'string', 'max:255'],
            'conditions'              => ['nullable', 'array'],
            'conditions.*'            => ['string', 'max:50'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'status'                  => ['required', 'in:active,inactive'],
        ];
    }
}
