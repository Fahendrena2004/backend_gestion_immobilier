<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Models\User;
use App\Modules\Demandes\Models\DemandeLocation;
use App\Modules\Finances\Models\Facture;
use App\Modules\Finances\Models\Paiement;
use App\Modules\Logements\Models\Logement;
use App\Shared\Enums\DemandeStatus;
use App\Shared\Enums\FactureStatus;
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
            'users_par_role'             => $usersParRole,
            'logements_par_statut'       => $logementsParStatut,
            'logements_par_moderation'   => $logementsParModeration,
            'demandes_en_attente'        => $demandesEnAttente,
            'paiements_en_attente'       => $paiementsEnAttente,
            'revenus_mois_courant'       => $revenusMoisCourant,
        ], 'Statistiques du tableau de bord');
    }
}
