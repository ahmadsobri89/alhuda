<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'          => ['required', 'exists:patients,id'],
            'prescribing_doctor'  => ['required', 'string', 'max:255'],
            'notes'               => ['nullable', 'string', 'max:1000'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.drug_name'   => ['required', 'string', 'max:255'],
            'items.*.kegunaan'    => ['nullable', 'string', 'max:255'],
            'items.*.drug_unit'   => ['nullable', 'string', 'max:100'],
            'items.*.dosage'      => ['nullable', 'string', 'max:100'],
            'items.*.frequency'   => ['nullable', 'string', 'max:100'],
            'items.*.duration'    => ['nullable', 'string', 'max:100'],
            'items.*.quantity'        => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.instructions'    => ['nullable', 'string', 'max:255'],
            'items.*.is_prn'          => ['boolean'],
            'items.*.complete_course' => ['boolean'],
        ];
    }
}
