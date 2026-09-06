<?php

namespace App\Modules\Logements\Http\Requests;

use App\Shared\Enums\ModerationStatus;
use App\Shared\Enums\LogementStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreLogementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quartier_id'      => ['required', 'exists:quartiers,id'],
            'type_logement_id' => ['required', 'exists:types_logement,id'],
            'titre'            => ['required', 'string', 'max:150'],
            'description'      => ['nullable', 'string'],
            'adresse'          => ['nullable', 'string', 'max:200'],
            'superficie'       => ['nullable', 'numeric', 'gt:0'],
            'nombre_pieces'    => ['nullable', 'integer', 'min:1'],
            'loyer'            => ['required', 'numeric', 'gt:0'],
            'caution'          => ['nullable', 'numeric', 'min:0'],
            'equipements'      => ['nullable', 'array'],
            'equipements.*'    => ['exists:equipements,id'],
        ];
    }
}
