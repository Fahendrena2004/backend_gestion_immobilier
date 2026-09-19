<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Models\User;
use App\Modules\Demandes\Models\DemandeLocation;
use App\Modules\Finances\Models\Facture;
use App\Modules\Finances\Models\Paiement;
use App\Modules\Locations\Models\Location;
use App\Modules\Logements\Models\Logement;
use App\Shared\Enums\DemandeStatus;
use App\Shared\Enums\FactureStatus;
use App\Shared\Enums\LocationStatus;
use App\Shared\Enums\PaymentStatus;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    use ApiResponseTrait;

    public function stats(): JsonResponse
    {
        $usersParRole = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role')
            ->toArray();

        $logementsParStatut = Logement::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $logementsParModeration = Logement::select('statut_moderation', DB::raw('count(*) as total'))
            ->groupBy('statut_moderation')
            ->pluck('total', 'statut_moderation')
            ->toArray();

        $demandesEnAttente = DemandeLocation::where('statut', DemandeStatus::EN_ATTENTE)->count();

        $paiementsEnAttente = Paiement::where('statut', PaymentStatus::DECLARE)->count();

        $revenusMoisCourant = (float) Facture::where('statut', FactureStatus::PAYEE)
            ->whereMonth('date_emission', now()->month)
            ->whereYear('date_emission', now()->year)
            ->sum('montant');

        return $this->successResponse([
            'users_par_role'           => $usersParRole,
            'utilisateurs_total'       => array_sum($usersParRole),
            'logements_par_statut'     => $logementsParStatut,
            'logements_par_moderation' => $logementsParModeration,
            'demandes_en_attente'      => $demandesEnAttente,
            'paiements_en_attente'     => $paiementsEnAttente,
            'revenus_mois_courant'     => $revenusMoisCourant,
            'locations_actives'        => Location::where('statut', LocationStatus::EN_COURS)->count(),
            'demandes_par_mois'        => $this->demandesParMois(),
            'revenus_par_mois'         => $this->revenusParMois(),
        ], 'Statistiques du tableau de bord');
    }

    /**
     * Nombre de demandes de location déposées sur les 6 derniers mois,
     * du plus ancien au plus récent (mois sans activité inclus).
     *
     * @return array<int, array{mois: string, valeur: int}>
     */
    private function demandesParMois(int $nombreDeMois = 6): array
    {
        $debut = now()->startOfMonth()->subMonths($nombreDeMois - 1);

        $comptes = DemandeLocation::where('created_at', '>=', $debut)
            ->get(['created_at'])
            ->groupBy(fn ($demande) => $demande->created_at->format('Y-m'))
            ->map(fn ($groupe) => $groupe->count());

        return $this->serieMensuelle($nombreDeMois, fn (string $cle) => (int) ($comptes[$cle] ?? 0));
    }

    /**
     * Montant encaissé (factures payées) sur les 6 derniers mois.
     *
     * @return array<int, array{mois: string, valeur: float}>
     */
    private function revenusParMois(int $nombreDeMois = 6): array
    {
        $debut = now()->startOfMonth()->subMonths($nombreDeMois - 1);

        $montants = Facture::where('statut', FactureStatus::PAYEE)
            ->where('date_emission', '>=', $debut->toDateString())
            ->get(['date_emission', 'montant'])
            ->groupBy(fn ($facture) => $facture->date_emission->format('Y-m'))
            ->map(fn ($groupe) => (float) $groupe->sum('montant'));

        return $this->serieMensuelle($nombreDeMois, fn (string $cle) => (float) ($montants[$cle] ?? 0));
    }

    /**
     * Construit une série mensuelle continue : un point par mois, libellé court
     * (« janv. »), même quand aucune donnée n'existe pour ce mois.
     */
    private function serieMensuelle(int $nombreDeMois, callable $valeurPourMois): array
    {
        $serie = [];

        for ($i = $nombreDeMois - 1; $i >= 0; $i--) {
            $mois = now()->startOfMonth()->subMonths($i);

            $serie[] = [
                'mois'   => $mois->translatedFormat('M'),
                'periode' => $mois->format('Y-m'),
                'valeur' => $valeurPourMois($mois->format('Y-m')),
            ];
        }

        return $serie;
    }
}
