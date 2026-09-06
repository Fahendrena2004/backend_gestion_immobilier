<?php

namespace App\Modules\Finances\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:60', 'unique:modes_paiement,libelle'],
            'actif'   => ['nullable', 'boolean'],
        ];
    }
}
