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
            'quartier_id'      => ['sometimes', 'required_without:quartier_autre', 'nullable', 'exists:quartiers,id'],
            'quartier_autre'   => ['sometimes', 'required_without:quartier_id', 'nullable', 'string', 'max:100'],
            'type_logement_id' => ['sometimes', 'required_without:type_autre', 'nullable', 'exists:types_logement,id'],
            'type_autre'       => ['sometimes', 'required_without:type_logement_id', 'nullable', 'string', 'max:60'],
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
