<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'     => ['nullable', 'string', 'max:100', Rule::unique('services', 'code')->ignore($this->route('service'))],
            'name'     => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'price'    => ['required', 'numeric', 'min:0'],
            'notes'    => ['nullable', 'string', 'max:1000'],
            'status'   => ['required', 'in:active,discontinued'],
        ];
    }
}
