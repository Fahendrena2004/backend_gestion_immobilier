<?php

namespace App\Modules\Finances\Http\Controllers;

use App\Modules\Finances\Http\Requests\StoreModePaiementRequest;
use App\Modules\Finances\Http\Requests\UpdateModePaiementRequest;
use App\Modules\Finances\Models\ModePaiement;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ModePaiementController
{
    use ApiResponseTrait;

    public function index(Request $request): LengthAwarePaginator
    {
        return ModePaiement::orderByDesc('created_at')->paginate(15);
    }

    public function store(StoreModePaiementRequest $request): JsonResponse
    {
        $mode = ModePaiement::create([
            'libelle' => $request->libelle,
            'actif'   => $request->boolean('actif', true),
        ]);

        return $this->successResponse($mode, 'Mode de paiement créé avec succès', 201);
    }

    public function update(UpdateModePaiementRequest $request, ModePaiement $mode): JsonResponse
    {
        $mode->update($request->validated());

        return $this->successResponse($mode, 'Mode de paiement mis à jour');
    }

    public function destroy(ModePaiement $mode): JsonResponse
    {
        if ($mode->paiements()->exists()) {
            return $this->errorResponse(
                'Ce mode de paiement est utilisé par des paiements existants et ne peut pas être supprimé.',
                422
            );
        }

        $mode->delete();

        return $this->successResponse(null, 'Mode de paiement supprimé');
    }
}
