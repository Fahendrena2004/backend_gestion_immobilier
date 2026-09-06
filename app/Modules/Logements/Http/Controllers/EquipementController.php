<?php

namespace App\Modules\Logements\Http\Controllers;

use App\Modules\Logements\Models\Logement;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipementController
{
    use ApiResponseTrait;

    public function sync(Request $request, Logement $logement): JsonResponse
    {
        if ($logement->proprietaire_id !== $request->user()->id) {
            return $this->errorResponse('Vous ne pouvez modifier que vos propres logements', 403);
        }

        $request->validate([
            'equipements'   => ['required', 'array'],
            'equipements.*' => ['exists:equipements,id'],
        ]);

        $logement->equipements()->sync($request->equipements);

        $logement->load('equipements');

        return $this->successResponse(
            $logement->equipements->map(fn ($eq) => [
                'id'      => $eq->id,
                'libelle' => $eq->libelle,
            ]),
            'Équipements mis à jour'
        );
    }
}
