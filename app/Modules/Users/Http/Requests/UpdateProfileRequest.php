<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'telephone'  => ['sometimes', 'string'],
            'cin'        => ['sometimes', 'string', 'unique:users,cin,' . $userId],
            'profession' => ['sometimes', 'nullable', 'string'],
            'adresse'    => ['sometimes', 'nullable', 'string'],
            'avatar'     => ['sometimes', 'nullable', 'string'],
        ];
    }
}
