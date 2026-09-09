<?php

namespace App\Modules\Locations\Http\Controllers;

use App\Modules\Locations\Http\Requests\StoreLocationRequest;
use App\Modules\Locations\Models\Contrat;
use App\Modules\Locations\Models\Location;
use App\Modules\Demandes\Models\DemandeLocation;
use App\Modules\Logements\Models\Logement;
use App\Shared\Enums\DemandeStatus;
use App\Shared\Enums\LocationStatus;
use App\Shared\Enums\LogementStatus;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LocationController
{
    use ApiResponseTrait;

    public function index(Request $request): LengthAwarePaginator|JsonResponse
    {
        $user = $request->user();

        if ($user->isLocataire()) {
            $locations = Location::where('locataire_id', $user->id)
                ->with(['logement.quartier', 'contrat'])
                ->orderByDesc('created_at')
                ->paginate(15);

            return $locations;
        }

        if ($user->isProprietaire()) {
            $locations = Location::whereHas('logement', function ($query) use ($user) {
                $query->where('proprietaire_id', $user->id);
            })
                ->with(['logement.quartier', 'contrat'])
                ->orderByDesc('created_at')
                ->paginate(15);

            return $locations;
        }

        return $this->successResponse([], 'Aucune location disponible');
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $user = $request->user();

        $demande = DemandeLocation::with('logement')->findOrFail($request->demande_id);

        if ($demande->logement->proprietaire_id !== $user->id) {
            return $this->errorResponse(
                'Cette demande ne concerne pas un de vos logements.',
                403
            );
        }

        if ($demande->statut !== DemandeStatus::ACCEPTEE) {
            return $this->errorResponse(
                'Cette demande n\'est pas au statut acceptée. Seules les demandes acceptées peuvent être transformées en contrat de location.',
                422
            );
        }

        $locationExistante = Location::where('demande_id', $demande->id)->exists();

        if ($locationExistante) {
            return $this->errorResponse(
                'Une location a déjà été créée pour cette demande.',
                422
            );
        }

        $location = DB::transaction(function () use ($request, $demande, $user) {
            $location = Location::create([
                'locataire_id' => $demande->locataire_id,
                'logement_id'  => $demande->logement_id,
                'demande_id'   => $demande->id,
                'date_debut'   => $request->date_debut,
                'statut'       => LocationStatus::EN_COURS,
            ]);

            $demande->logement->update(['statut' => LogementStatus::LOUE]);

            Contrat::create([
                'location_id'     => $location->id,
                'date_signature'  => now()->toDateString(),
                'date_debut'      => $request->date_debut,
                'montant_loyer'   => $request->montant_loyer,
                'montant_caution' => $request->montant_caution,
                'conditions'      => $request->conditions,
            ]);

            return $location;
        });

        $location->load(['logement.quartier', 'contrat']);

        return $this->successResponse($location, 'Contrat de location créé avec succès', 201);
    }
}
