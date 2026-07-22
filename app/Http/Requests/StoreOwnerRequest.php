<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'status' => ['required', 'string', 'in:proprietaire,locataire'],
            'is_tenant' => ['required', 'boolean'],
            'lot_surface' => ['required', 'numeric', 'min:0'],
            'surface_confirmation' => ['required', 'numeric', 'same:lot_surface'],
            'has_mezzanine' => ['required', 'boolean'],
            'mezzanine_surface' => ['nullable', 'numeric', 'min:0'],
            'office_number' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'integer'],
            'is_council_member' => ['sometimes', 'boolean'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'real_owner_name' => ['nullable', 'string', 'max:255'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('mezzanine_surface', ['required', 'numeric', 'min:0'], function ($input) {
            return $input->has_mezzanine == true;
        });

        $validator->sometimes(['contract_start_date', 'contract_end_date', 'real_owner_name'], ['required'], function ($input) {
            return $input->status === 'locataire';
        });

        $validator->sometimes('contract_end_date', ['after:contract_start_date'], function ($input) {
            return $input->status === 'locataire';
        });
    }
}