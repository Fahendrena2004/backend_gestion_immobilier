<?php

namespace App\Modules\Finances\Http\Controllers;

use App\Modules\Finances\Models\Facture;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class FactureController
{
    use ApiResponseTrait;

    public function index(Request $request): LengthAwarePaginator|JsonResponse
    {
        $user = $request->user();

        if ($user->isLocataire()) {
            $factures = Facture::whereHas('location', function ($query) use ($user) {
                $query->where('locataire_id', $user->id);
            })
                ->with('location.logement')
                ->orderByDesc('created_at')
                ->paginate(15);

            return $factures;
        }

        if ($user->isProprietaire()) {
            $factures = Facture::whereHas('location.logement', function ($query) use ($user) {
                $query->where('proprietaire_id', $user->id);
            })
                ->with('location.logement')
                ->orderByDesc('created_at')
                ->paginate(15);

            return $factures;
        }

        return $this->successResponse([], 'Aucune facture disponible');
    }
}
