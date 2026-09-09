<?php

namespace App\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogementOwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $logement = $request->route('logement');

        if ($logement && $logement->proprietaire_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que vos propres logements.',
            ], 403);
        }

        return $next($request);
    }
}
