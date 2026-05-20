<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', "unique:users,email,{$userId}"],
            'role'        => ['required', 'in:doctor,nurse,pharmacist,receptionist,admin'],
            'mmc_number'  => ['nullable', 'string', 'max:50'],
            'mfa_enabled' => ['boolean'],
            'status'      => ['required', 'in:active,inactive'],
            'password'    => ['nullable', Password::min(8)],
        ];
    }
}
