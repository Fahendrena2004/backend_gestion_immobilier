<?php

namespace App\Modules\Logements\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLogementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quartier_id'      => ['sometimes', 'required', 'exists:quartiers,id'],
            'type_logement_id' => ['sometimes', 'required', 'exists:types_logement,id'],
            'titre'            => ['sometimes', 'required', 'string', 'max:150'],
            'description'      => ['nullable', 'string'],
            'adresse'          => ['nullable', 'string', 'max:200'],
            'superficie'       => ['nullable', 'numeric', 'gt:0'],
            'nombre_pieces'    => ['nullable', 'integer', 'min:1'],
            'loyer'            => ['sometimes', 'required', 'numeric', 'gt:0'],
            'caution'          => ['nullable', 'numeric', 'min:0'],
            'statut'           => ['sometimes', 'string', 'in:disponible,reserve,loue,indisponible'],
            'equipements'      => ['nullable', 'array'],
            'equipements.*'    => ['exists:equipements,id'],
        ];
    }
}
