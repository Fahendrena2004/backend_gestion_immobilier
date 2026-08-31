<?php

namespace App\Shared\Middleware;

use App\Shared\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Manamarina ny andraikitra (Role) an'ny mpampiasa
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Tsy nahazoana alalana (Unauthenticated).'
            ], 401);
        }

        // Raha manana role attribute ny user
        $userRole = $user->role instanceof UserRole ? $user->role->value : $user->role;

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Tsy manana alalana amin\'ity asa ity ianao (Unauthorized).'
            ], 403);
        }

        return $next($request);
    }
}
