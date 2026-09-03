<?php

namespace App\Modules\Auth\Http\Requests;

use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
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
            'role'       => ['required', new Enum(UserRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'role.not_in' => 'Le rôle doit être locataire ou propriétaire.',
        ];
    }
}
