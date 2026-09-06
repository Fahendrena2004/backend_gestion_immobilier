<?php

namespace App\Modules\Demandes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDemandeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', 'string', 'in:acceptee,refusee,annulee'],
        ];
    }
}
