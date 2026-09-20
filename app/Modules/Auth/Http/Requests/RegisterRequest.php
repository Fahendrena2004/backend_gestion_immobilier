<?php

namespace App\Modules\Auth\Http\Requests;

use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'confirmed', Password::defaults()],
            'telephone'  => ['required', 'string', 'max:20'],
            'cin'        => ['required', 'string', 'max:20', 'unique:users,cin'],
            'profession' => ['nullable', 'string', 'max:100'],
            'adresse'    => ['nullable', 'string', 'max:200'],
            // L'inscription publique ne permet que les rôles locataire et
            // propriétaire : un compte administrateur se crée en base (seeder).
            'role'       => ['required', Rule::in([
                UserRole::LOCATAIRE->value,
                UserRole::PROPRIETAIRE->value,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Le rôle doit être locataire ou propriétaire.',
        ];
    }
}
