<?php

namespace App\Modules\Locations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'demande_id'      => ['required', 'exists:demandes_location,id'],
            'date_debut'      => ['required', 'date', 'after_or_equal:today'],
            'montant_loyer'   => ['required', 'numeric', 'min:0'],
            'montant_caution' => ['nullable', 'numeric', 'min:0'],
            'conditions'      => ['nullable', 'string'],
        ];
    }
}
