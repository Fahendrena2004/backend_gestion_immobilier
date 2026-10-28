<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Modules\Logements\Models\Logement;
use App\Modules\Logements\Models\Quartier;
use App\Modules\Logements\Models\TypeLogement;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferentielController
{
    use ApiResponseTrait;

    /**
     * Liste des quartiers et types de logement en attente de validation.
     */
    public function enAttente(): JsonResponse
    {
        $quartiers = Quartier::where('est_valide', false)
            ->withCount('logements')
            ->orderBy('nom')
            ->get()
            ->map(fn (Quartier $q) => [
                'id'              => $q->id,
                'libelle'         => $q->nom,
                'ville'           => $q->ville,
                'logements_count' => $q->logements_count,
            ]);

        $types = TypeLogement::where('est_valide', false)
            ->withCount('logements')
            ->orderBy('libelle')
            ->get()
            ->map(fn (TypeLogement $t) => [
                'id'              => $t->id,
                'libelle'         => $t->libelle,
                'logements_count' => $t->logements_count,
            ]);

        return $this->successResponse([
            'quartiers' => $quartiers,
            'types'     => $types,
        ]);
    }

    /**
     * Valider une entrée de référentiel.
     */
    public function valider(string $ressource, int $id): JsonResponse
    {
        if ($ressource !== 'quartiers' && $ressource !== 'types') {
            return $this->errorResponse('Ressource introuvable', 404);
        }

        $entite = $ressource === 'quartiers'
            ? Quartier::find($id)
            : TypeLogement::find($id);

        if (!$entite) {
            return $this->errorResponse('Élément introuvable', 404);
        }

        $entite->update(['est_valide' => true]);

        return $this->successResponse($entite, 'Référentiel validé avec succès');
    }

    /**
     * Fusionner une entrée en attente avec une entrée validée existante.
     */
    public function fusionner(Request $request, string $ressource, int $id): JsonResponse
    {
        if ($ressource !== 'quartiers' && $ressource !== 'types') {
            return $this->errorResponse('Ressource introuvable', 404);
        }

        $table = $ressource === 'quartiers' ? 'quartiers' : 'types_logement';
        $modelClass = $ressource === 'quartiers' ? Quartier::class : TypeLogement::class;
        $cleEtrangere = $ressource === 'quartiers' ? 'quartier_id' : 'type_logement_id';

        $source = $modelClass::find($id);
        if (!$source) {
            return $this->errorResponse('Élément source introuvable', 404);
        }

        $validated = $request->validate([
            'cible_id' => ['required', 'integer', "exists:{$table},id"],
        ]);

        $cibleId = (int) $validated['cible_id'];

        if ($cibleId === (int) $id) {
            return $this->errorResponse('Impossible de fusionner une valeur avec elle-même.', 422, [
                'cible_id' => ['La cible ne peut pas être identique à la source.'],
            ]);
        }

        $cible = $modelClass::find($cibleId);
        if (!$cible || !$cible->est_valide) {
            return $this->errorResponse('La valeur cible doit être validée.', 422, [
                'cible_id' => ['La valeur cible sélectionnée n\'est pas validée.'],
            ]);
        }

        $logementsReaffectes = DB::transaction(function () use ($cleEtrangere, $id, $cibleId, $source) {
            $nombre = Logement::where($cleEtrangere, $id)->update([$cleEtrangere => $cibleId]);
            $source->delete();
            return $nombre;
        });

        return $this->successResponse([
            'logements_reaffectes' => $logementsReaffectes,
        ], "Fusion effectuée avec succès ({$logementsReaffectes} logement(s) réaffecté(s))");
    }
}
