<?php

namespace App\Modules\Visites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisiteStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut'       => ['required', 'string', 'in:proposee,confirmee,annulee,realisee'],
            'date_proposee' => ['required_if:statut,proposee', 'nullable', 'date', 'after:now'],
            'resultat'     => ['nullable', 'string', 'max:255'],
        ];
    }
}
