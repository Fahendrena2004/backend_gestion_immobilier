<?php

namespace App\Modules\Demandes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logement_id' => ['required', 'exists:logements,id'],
            'message'     => ['nullable', 'string'],
        ];
    }
}
