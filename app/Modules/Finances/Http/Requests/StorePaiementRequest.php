<?php

namespace App\Modules\Finances\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facture_id'       => ['required', 'exists:factures,id'],
            'mode_paiement_id' => ['required', 'exists:modes_paiement,id'],
            'montant'          => ['required', 'numeric', 'min:0.01'],
            'reference'        => ['nullable', 'string', 'max:100'],
            'preuve'           => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
