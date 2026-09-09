<?php

namespace App\Modules\Logements\Http\Controllers;

use App\Modules\Logements\Http\Requests\StoreLogementRequest;
use App\Modules\Logements\Http\Requests\UpdateLogementRequest;
use App\Modules\Logements\Models\Logement;
use App\Modules\Logements\Models\Quartier;
use App\Modules\Logements\Models\TypeLogement;
use App\Modules\Logements\Resources\LogementListResource;
use App\Modules\Logements\Resources\LogementResource;
use App\Shared\Enums\ModerationStatus;
use App\Shared\Enums\LogementStatus;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogementController
{
    use ApiResponseTrait;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Logement::with(['quartier', 'typeLogement', 'photos', 'equipements'])
            ->where('statut_moderation', ModerationStatus::APPROUVE)
            ->where('statut', LogementStatus::DISPONIBLE);

        if ($request->filled('quartier_id')) {
            $query->where('quartier_id', $request->quartier_id);
        }
        if ($request->filled('type_logement_id')) {
            $query->where('type_logement_id', $request->type_logement_id);
        }
        if ($request->filled('loyer_min')) {
            $query->where('loyer', '>=', $request->loyer_min);
        }
        if ($request->filled('loyer_max')) {
            $query->where('loyer', '<=', $request->loyer_max);
        }
        if ($request->filled('nombre_pieces')) {
            $query->where('nombre_pieces', $request->nombre_pieces);
        }

        $logements = $query->orderByDesc('created_at')->paginate(15);

        return LogementListResource::collection($logements);
    }

    public function quartiers(): JsonResponse
    {
        $quartiers = Quartier::orderBy('nom')->get();

        return $this->successResponse($quartiers);
    }

    public function types(): JsonResponse
    {
        $typesLogement = TypeLogement::orderBy('libelle')->get();

        return $this->successResponse($typesLogement);
    }

    public function show(Request $request, Logement $logement): LogementResource|JsonResponse
    {
        if ($logement->statut_moderation !== ModerationStatus::APPROUVE) {
            $user = $request->user('sanctum');
            $isOwner = $user && $logement->proprietaire_id === $user->id;
            $isAdmin = $user && $user->isAdmin();

            if (!$isOwner && !$isAdmin) {
                return $this->errorResponse('Ressource introuvable', 404);
            }
        }

        $logement->load(['quartier', 'typeLogement', 'photos', 'equipements', 'proprietaire']);

        return new LogementResource($logement);
    }

    public function store(StoreLogementRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $equipements = $validated['equipements'] ?? [];
        unset($validated['equipements']);

        $logement = $request->user()->logements()->create([
            ...$validated,
            'statut' => LogementStatus::DISPONIBLE,
            'statut_moderation' => ModerationStatus::EN_ATTENTE,
        ]);

        if ($equipements) {
            $logement->equipements()->sync($equipements);
        }

        $logement->load(['quartier', 'typeLogement', 'equipements']);

        return $this->successResponse(
            new LogementResource($logement),
            'Logement créé avec succès',
            201
        );
    }

    public function update(UpdateLogementRequest $request, Logement $logement): JsonResponse
    {
        if ($logement->proprietaire_id !== $request->user()->id) {
            return $this->errorResponse('Vous ne pouvez modifier que vos propres logements', 403);
        }

        $validated = $request->validated();
        $equipements = $validated['equipements'] ?? null;
        unset($validated['equipements']);

        $logement->update($validated);

        if ($equipements !== null) {
            $logement->equipements()->sync($equipements);
        }

        $logement->load(['quartier', 'typeLogement', 'photos', 'equipements']);

        return $this->successResponse(
            new LogementResource($logement),
            'Logement mis à jour avec succès'
        );
    }

    public function destroy(Request $request, Logement $logement): JsonResponse
    {
        if ($logement->proprietaire_id !== $request->user()->id) {
            return $this->errorResponse('Vous ne pouvez supprimer que vos propres logements', 403);
        }

        $logement->equipements()->detach();
        $logement->delete();

        return $this->successResponse(null, 'Logement supprimé avec succès');
    }

    public function mesAnnonces(Request $request): AnonymousResourceCollection
    {
        $logements = $request->user()->logements()
            ->with(['quartier', 'typeLogement', 'photos'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return LogementListResource::collection($logements);
    }
}
