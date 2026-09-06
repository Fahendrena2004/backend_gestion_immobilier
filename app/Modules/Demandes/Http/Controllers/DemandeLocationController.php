<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Modules\Demandes\Http\Requests\StoreDemandeRequest;
use App\Modules\Demandes\Http\Requests\UpdateDemandeStatusRequest;
use App\Modules\Demandes\Models\DemandeLocation;
use App\Modules\Notifications\Models\Notification;
use App\Shared\Enums\DemandeStatus;
use App\Shared\Enums\LogementStatus;
use App\Shared\Enums\ModerationStatus;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class DemandeLocationController
{
    use ApiResponseTrait;

    public function index(Request $request): LengthAwarePaginator|JsonResponse
    {
        $user = $request->user();

        if ($user->isLocataire()) {
            $demandes = DemandeLocation::where('locataire_id', $user->id)
                ->with('logement')
                ->orderByDesc('created_at')
                ->paginate(15);

            return $demandes;
        }

        if ($user->isProprietaire()) {
            $demandes = DemandeLocation::whereHas('logement', function ($query) use ($user) {
                $query->where('proprietaire_id', $user->id);
            })
                ->with('logement')
                ->orderByDesc('created_at')
                ->paginate(15);

            return $demandes;
        }

        return $this->successResponse([], 'Aucune demande disponible');
    }

    public function store(StoreDemandeRequest $request): JsonResponse
    {
        $logement = \App\Modules\Logements\Models\Logement::findOrFail($request->logement_id);

        if ($logement->statut !== LogementStatus::DISPONIBLE
            || $logement->statut_moderation !== ModerationStatus::APPROUVE) {
            return $this->errorResponse(
                'Ce logement n\'est pas disponible pour une demande de location.',
                422
            );
        }

        $existingDemande = DemandeLocation::where('locataire_id', $request->user()->id)
            ->where('logement_id', $logement->id)
            ->where('statut', DemandeStatus::EN_ATTENTE)
            ->exists();

        if ($existingDemande) {
            return $this->errorResponse(
                'Vous avez déjà une demande en attente pour ce logement.',
                422
            );
        }

        $demande = DemandeLocation::create([
            'locataire_id' => $request->user()->id,
            'logement_id'  => $logement->id,
            'message'      => $request->message,
            'statut'       => DemandeStatus::EN_ATTENTE,
        ]);

        $demande->load('logement');

        return $this->successResponse($demande, 'Demande créée avec succès', 201);
    }

    public function updateStatus(UpdateDemandeStatusRequest $request, DemandeLocation $demande): JsonResponse
    {
        if ($demande->statut !== DemandeStatus::EN_ATTENTE) {
            return $this->errorResponse(
                'Cette demande n\'est plus en attente. Aucune modification possible.',
                422
            );
        }

        $user = $request->user();
        $nouveauStatut = DemandeStatus::from($request->statut);

        if ($nouveauStatut === DemandeStatus::ACCEPTEE || $nouveauStatut === DemandeStatus::REFUSEE) {
            if ($demande->logement->proprietaire_id !== $user->id) {
                return $this->errorResponse(
                    'Seul le propriétaire du logement peut accepter ou refuser cette demande.',
                    403
                );
            }
        }

        if ($nouveauStatut === DemandeStatus::ANNULEE) {
            if ($demande->locataire_id !== $user->id) {
                return $this->errorResponse(
                    'Seul l\'auteur de la demande peut l\'annuler.',
                    403
                );
            }
        }

        $demande->update(['statut' => $nouveauStatut]);

        if ($nouveauStatut === DemandeStatus::ACCEPTEE) {
            Notification::create([
                'user_id' => $demande->locataire_id,
                'titre'   => 'Votre demande a été acceptée',
                'contenu' => "Votre demande pour le logement «{$demande->logement->titre}» a été acceptée par le propriétaire.",
                'lu'      => false,
            ]);
        }

        $demande->load('logement');

        return $this->successResponse($demande, 'Statut mis à jour avec succès');
    }
}
