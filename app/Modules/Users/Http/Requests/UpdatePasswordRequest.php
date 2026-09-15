<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
            'new_password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'     => 'Le mot de passe actuel est obligatoire.',
            'new_password.required'         => 'Le nouveau mot de passe est obligatoire.',
            'new_password.min'              => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'new_password.confirmed'        => 'La confirmation du nouveau mot de passe ne correspond pas.',
            'new_password_confirmation.required' => 'La confirmation du nouveau mot de passe est obligatoire.',
        ];
    }
}