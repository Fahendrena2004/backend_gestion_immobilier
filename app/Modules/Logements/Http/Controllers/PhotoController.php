<?php

namespace App\Modules\Logements\Http\Controllers;

use App\Modules\Logements\Models\Logement;
use App\Modules\Logements\Models\Photo;
use App\Shared\Services\FileUploadService;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotoController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly FileUploadService $fileUploadService
    ) {}

    public function store(Request $request, Logement $logement): JsonResponse
    {
        if ($logement->proprietaire_id !== $request->user()->id) {
            return $this->errorResponse('Vous ne pouvez modifier que vos propres logements', 403);
        }

        $request->validate([
            'photo'   => ['required', 'image', 'max:5120'],
            'est_principale' => ['sometimes', 'boolean'],
        ]);

        $path = $this->fileUploadService->upload($request->file('photo'), 'logements');

        $estPrincipale = $request->boolean('est_principale', false);

        if ($estPrincipale) {
            $logement->photos()->update(['est_principale' => false]);
        }

        $photo = $logement->photos()->create([
            'chemin' => $path,
            'est_principale' => $estPrincipale,
        ]);

        return $this->successResponse([
            'id'            => $photo->id,
            'chemin'        => url('storage/' . $photo->chemin),
            'est_principale' => $photo->est_principale,
        ], 'Photo uploadée avec succès', 201);
    }

    public function destroy(Request $request, Logement $logement, Photo $photo): JsonResponse
    {
        if ($logement->proprietaire_id !== $request->user()->id) {
            return $this->errorResponse('Vous ne pouvez modifier que vos propres logements', 403);
        }

        if ($photo->logement_id !== $logement->id) {
            return $this->errorResponse('Cette photo n\'appartient pas à ce logement', 404);
        }

        $this->fileUploadService->delete($photo->chemin);
        $photo->delete();

        return $this->successResponse(null, 'Photo supprimée avec succès');
    }

    public function setPrincipale(Request $request, Logement $logement, Photo $photo): JsonResponse
    {
        if ($logement->proprietaire_id !== $request->user()->id) {
            return $this->errorResponse('Vous ne pouvez modifier que vos propres logements', 403);
        }

        if ($photo->logement_id !== $logement->id) {
            return $this->errorResponse('Cette photo n\'appartient pas à ce logement', 404);
        }

        $logement->photos()->update(['est_principale' => false]);
        $photo->update(['est_principale' => true]);

        return $this->successResponse([
            'id'            => $photo->id,
            'chemin'        => url('storage/' . $photo->chemin),
            'est_principale' => true,
        ], 'Photo principale définie');
    }
}
