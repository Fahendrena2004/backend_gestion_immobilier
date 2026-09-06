<?php

namespace App\Modules\Visites\Http\Controllers;

use App\Modules\Visites\Http\Requests\StoreVisiteRequest;
use App\Modules\Visites\Http\Requests\UpdateVisiteStatusRequest;
use App\Modules\Visites\Models\Visite;
use App\Modules\Logements\Models\Logement;
use App\Modules\Notifications\Models\Notification;
use App\Shared\Enums\LogementStatus;
use App\Shared\Enums\ModerationStatus;
use App\Shared\Enums\VisiteStatus;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class VisiteController
{
    use ApiResponseTrait;

    private array $allowedTransitions = [
        'demandee' => [
            'proposee' => ['proprietaire'],
            'annulee'  => ['locataire', 'proprietaire'],
        ],
        'proposee' => [
            'confirmee' => ['locataire'],
            'annulee'   => ['locataire', 'proprietaire'],
        ],
        'confirmee' => [
            'realisee' => ['proprietaire'],
            'annulee'  => ['locataire', 'proprietaire'],
        ],
    ];

    public function index(Request $request): LengthAwarePaginator|JsonResponse
    {
        $user = $request->user();

        if ($user->isLocataire()) {
            return Visite::where('locataire_id', $user->id)
                ->with('logement')
                ->orderByDesc('created_at')
                ->paginate(15);
        }

        if ($user->isProprietaire()) {
            return Visite::whereHas('logement', function ($query) use ($user) {
                $query->where('proprietaire_id', $user->id);
            })
                ->with('logement')
                ->orderByDesc('created_at')
                ->paginate(15);
        }

        return $this->successResponse([], 'Aucune visite disponible');
    }

    public function store(StoreVisiteRequest $request): JsonResponse
    {
        $logement = Logement::findOrFail($request->logement_id);

        if ($logement->statut !== LogementStatus::DISPONIBLE
            || $logement->statut_moderation !== ModerationStatus::APPROUVE) {
            return $this->errorResponse(
                'Ce logement n\'est pas disponible pour une visite.',
                422
            );
        }

        $visite = Visite::create([
            'locataire_id' => $request->user()->id,
            'logement_id'  => $logement->id,
            'statut'       => VisiteStatus::DEMANDEE,
        ]);

        $visite->load('logement');

        return $this->successResponse($visite, 'Visite planifiée avec succès', 201);
    }

    public function updateStatus(UpdateVisiteStatusRequest $request, Visite $visite): JsonResponse
    {
        $user = $request->user();
        $nouveauStatut = VisiteStatus::from($request->statut);

        if ($nouveauStatut === $visite->statut) {
            return $this->errorResponse(
                'La visite est déjà au statut « ' . $visite->statut->value . ' ».',
                422
            );
        }

        $transitionsPossibles = $this->allowedTransitions[$visite->statut->value] ?? [];

        if (!isset($transitionsPossibles[$nouveauStatut->value])) {
            return $this->errorResponse(
                'Transition invalide : ' . $visite->statut->value . ' → ' . $nouveauStatut->value . '.',
                422
            );
        }

        $rolesAutorises = $transitionsPossibles[$nouveauStatut->value];
        $estProprietaire = $user->isProprietaire();
        $estLocataire = $user->isLocataire();

        $roleRequis = match (true) {
            $estProprietaire && in_array('proprietaire', $rolesAutorises) => true,
            $estLocataire && in_array('locataire', $rolesAutorises) => true,
            default => false,
        };

        if (!$roleRequis) {
            return $this->errorResponse(
                'Vous n\'avez pas les droits pour effectuer cette transition.',
                403
            );
        }

        $data = ['statut' => $nouveauStatut];

        if ($nouveauStatut === VisiteStatus::PROPOSEE) {
            $data['date_proposee'] = $request->date_proposee;

            Notification::create([
                'user_id' => $visite->locataire_id,
                'titre'   => 'Une date de visite a été proposée',
                'contenu' => "Une date de visite pour le logement «{$visite->logement->titre}» vous a été proposée.",
                'lu'      => false,
            ]);
        }

        if ($nouveauStatut === VisiteStatus::CONFIRMEE) {
            Notification::create([
                'user_id' => $visite->logement->proprietaire_id,
                'titre'   => 'Visite confirmée',
                'contenu' => "Le locataire a confirmé la visite pour le logement «{$visite->logement->titre}».",
                'lu'      => false,
            ]);
        }

        if ($nouveauStatut === VisiteStatus::REALISEE && $request->resultat) {
            $data['resultat'] = $request->resultat;
        }

        $visite->update($data);
        $visite->load('logement');

        return $this->successResponse($visite, 'Statut mis à jour avec succès');
    }
}
