<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'     => ['nullable', 'string', 'max:100', 'unique:services,code'],
            'name'     => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'price'    => ['required', 'numeric', 'min:0'],
            'notes'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}
