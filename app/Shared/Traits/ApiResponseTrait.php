<?php

namespace App\Shared\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Valiny nahomby (Success response)
     */
    public function successResponse(mixed $data = null, string $message = 'Succès', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Valiny misy olana (Error response)
     */
    public function errorResponse(string $message = 'Nisy olana nitranga', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $statusCode);
    }
}
