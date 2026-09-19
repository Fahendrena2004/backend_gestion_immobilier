<?php

namespace App\Modules\Locations\Http\Controllers;

use App\Modules\Locations\Http\Requests\StoreLocationRequest;
use App\Modules\Locations\Models\Contrat;
use App\Modules\Locations\Models\Location;
use App\Modules\Demandes\Models\DemandeLocation;
use App\Modules\Finances\Models\Facture;
use App\Modules\Notifications\Models\Notification;
use App\Shared\Enums\DemandeStatus;
use App\Shared\Enums\FactureStatus;
use App\Shared\Enums\LocationStatus;
use App\Shared\Enums\LogementStatus;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

            $contrat = Contrat::create([
                'location_id'     => $location->id,
                'date_signature'  => now()->toDateString(),
                'date_debut'      => $request->date_debut,
                'montant_loyer'   => $request->montant_loyer,
                'montant_caution' => $request->montant_caution,
                'conditions'      => $request->conditions,
            ]);

            // Générer la première facture pour le premier mois de location
            $facture = $this->createFirstFacture($location, $contrat, $request->date_debut);

            Notification::create([
                'user_id' => $location->locataire_id,
                'titre'   => 'Votre contrat de location est disponible',
                'contenu' => "Votre location du logement «{$demande->logement->titre}» démarre le "
                    . $location->date_debut->format('d/m/Y')
                    . ". La facture {$facture->numero_facture} est à régler avant le "
                    . $facture->date_echeance->format('d/m/Y') . '.',
                'type'    => 'contrat_cree',
                'lu'      => false,
            ]);

            return $location;
        });

        $location->load(['logement.quartier', 'contrat']);

        return $this->successResponse($location, 'Contrat de location créé avec succès', 201);
    }

    /**
     * Crée la première facture pour le premier mois de location.
     *
     * @param Location $location
     * @param Contrat $contrat
     * @param string $dateDebut
     * @return Facture
     */
    private function createFirstFacture(Location $location, Contrat $contrat, string $dateDebut): Facture
    {
        $dateDebut = Carbon::parse($dateDebut);

        // Générer le numéro de facture : FAC-YYYY-NNNN
        $year = now()->year;
        $prefix = "FAC-{$year}-";

        $maxSequence = Facture::where('numero_facture', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->max(DB::raw("CAST(SUBSTRING(numero_facture, " . (strlen($prefix) + 1) . ") AS UNSIGNED)"));

        $sequence = ($maxSequence ?? 0) + 1;
        $numeroFacture = sprintf('FAC-%s-%04d', $year, $sequence);

        // Période : mois de la date de début (ex : « Octobre 2026 »)
        $periode = $dateDebut->translatedFormat('F Y');

        $dateEmission = now()->toDateString();

        // Échéance : 10 jours après la date de début, ou le 5 du mois suivant
        // si cette date est plus tardive.
        $dateEcheance = $dateDebut->copy()->addDays(10);
        $cinqDuMoisSuivant = $dateDebut->copy()->addMonth()->day(5);

        if ($cinqDuMoisSuivant->gt($dateEcheance)) {
            $dateEcheance = $cinqDuMoisSuivant;
        }

        $montant = $contrat->montant_loyer;

        return Facture::create([
            'location_id'   => $location->id,
            'numero_facture' => $numeroFacture,
            'date_emission' => $dateEmission,
            'date_echeance' => $dateEcheance->toDateString(),
            'montant'       => $montant,
            'periode'       => $periode,
            'statut'        => FactureStatus::IMPAYEE,
        ]);
    }
}