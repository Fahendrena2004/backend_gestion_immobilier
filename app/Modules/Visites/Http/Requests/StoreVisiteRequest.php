<?php

namespace App\Modules\Visites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logement_id' => ['required', 'exists:logements,id'],
        ];
    }
}
