<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'generic_name'   => ['nullable', 'string', 'max:255'],
            'form'           => ['required', 'string', 'max:100'],
            'category'       => ['nullable', 'string', 'max:100'],
            'classification' => ['required', 'in:general,poison_b,poison_c,controlled'],
            'lot_number'     => ['nullable', 'string', 'max:50'],
            'expiry_date'    => ['nullable', 'date'],
            'supplier'       => ['nullable', 'string', 'max:255'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'reorder_level'  => ['required', 'integer', 'min:0'],
            'unit_cost'      => ['required', 'numeric', 'min:0'],
            'unit'           => ['required', 'string', 'max:50'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }
}
