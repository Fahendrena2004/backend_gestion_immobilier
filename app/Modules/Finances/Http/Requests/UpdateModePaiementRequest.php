<?php

namespace App\Modules\Finances\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mode = $this->route('mode');
        $modeId = is_object($mode) ? $mode->id : $mode;

        return [
            'libelle' => ['sometimes', 'required', 'string', 'max:60', 'unique:modes_paiement,libelle,' . $modeId . ',id'],
            'actif'   => ['sometimes', 'boolean'],
        ];
    }
}
