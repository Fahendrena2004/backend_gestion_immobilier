<?php

namespace App\Modules\Administration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModerateLogementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut_moderation' => ['required', 'string', 'in:approuve,suspendu,supprime'],
        ];
    }
}
