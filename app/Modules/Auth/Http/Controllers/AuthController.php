<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController
{
    use ApiResponseTrait;

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => $validated['password'],
            'telephone'  => $validated['telephone'],
            'cin'        => $validated['cin'],
            'profession' => $validated['profession'] ?? null,
            'adresse'    => $validated['adresse'] ?? null,
            'role'       => $validated['role'],
            'is_active'  => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user'  => $user,
            'token' => $token,
        ], 'Inscription réussie', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Identifiants incorrects', 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user'  => $user,
            'token' => $token,
        ], 'Connexion réussie');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Déconnexion réussie');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($request->user(), 'Profil récupéré');
    }
}
