<?php

namespace App\Modules\Users\Http\Controllers;

use App\Models\User;
use App\Modules\Users\Http\Requests\UpdateProfileRequest;
use App\Modules\Users\Http\Requests\UpdatePasswordRequest;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Liste des utilisateurs récupérée avec succès',
            'data'    => $users->items(),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->successResponse(
            $request->user(),
            'Profil récupéré avec succès'
        );
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $allowed = ['name', 'telephone', 'cin', 'avatar'];

        if ($user->isLocataire()) {
            $allowed[] = 'profession';
        }

        if ($user->isProprietaire()) {
            $allowed[] = 'adresse';
        }

        $user->update($request->only($allowed));

        return $this->successResponse(
            $user->fresh(),
            'Profil mis à jour avec succès'
        );
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Mot de passe actuel incorrect', 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->successResponse(
            null,
            'Mot de passe modifié avec succès'
        );
    }
}
