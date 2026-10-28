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

    /**
     * Liste des logements disponibles et approuvés.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Logement::with([
            'quartier',
            'typeLogement',
            'photos',
            'equipements',
        ])
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

        // `nombre_pieces` = nombre exact, `pieces_min` = au moins N pièces.
        if ($request->filled('nombre_pieces')) {
            $query->where('nombre_pieces', $request->nombre_pieces);
        }

        if ($request->filled('pieces_min')) {
            $query->where('nombre_pieces', '>=', $request->pieces_min);
        }

        // Recherche plein texte simple sur le titre, la description, l'adresse
        // et le nom du quartier.
        if ($request->filled('q')) {
            $terme = '%' . $request->q . '%';

            $query->where(function ($sousRequete) use ($terme) {
                $sousRequete->where('titre', 'like', $terme)
                    ->orWhere('description', 'like', $terme)
                    ->orWhere('adresse', 'like', $terme)
                    ->orWhereHas('quartier', fn ($q) => $q->where('nom', 'like', $terme))
                    ->orWhereHas('typeLogement', fn ($q) => $q->where('libelle', 'like', $terme));
            });
        }

        // Le logement doit posséder TOUS les équipements demandés.
        $equipements = array_filter((array) $request->input('equipements', []));

        foreach ($equipements as $equipementId) {
            $query->whereHas('equipements', fn ($q) => $q->where('equipements.id', $equipementId));
        }

        $logements = $query
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return LogementListResource::collection($logements);
    }

    /**
     * Taille de page demandée, bornée pour éviter les requêtes trop lourdes.
     */
    private function perPage(Request $request, int $defaut = 15): int
    {
        $perPage = (int) $request->input('per_page', $defaut);

        return max(1, min($perPage, 100));
    }

    /**
     * Liste des quartiers.
     */
    public function quartiers(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $quartiers = Quartier::where(function ($query) use ($user) {
            $query->where('est_valide', true);
            if ($user) {
                $query->orWhere(function ($sub) use ($user) {
                    $sub->where('est_valide', false)
                        ->whereHas('logements', fn ($q) => $q->where('proprietaire_id', $user->id));
                });
            }
        })
            ->orderBy('nom')
            ->get();

        return $this->successResponse($quartiers);
    }

    /**
     * Liste des types de logements.
     */
    public function types(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $typesLogement = TypeLogement::where(function ($query) use ($user) {
            $query->where('est_valide', true);
            if ($user) {
                $query->orWhere(function ($sub) use ($user) {
                    $sub->where('est_valide', false)
                        ->whereHas('logements', fn ($q) => $q->where('proprietaire_id', $user->id));
                });
            }
        })
            ->orderBy('libelle')
            ->get();

        return $this->successResponse($typesLogement);
    }

    /**
     * Afficher un logement.
     */
    public function show(
        Request $request,
        Logement $logement
    ): LogementResource|JsonResponse {
        if ($logement->statut_moderation !== ModerationStatus::APPROUVE) {
            $user = $request->user('sanctum');

            $isOwner = $user && $logement->proprietaire_id === $user->id;
            $isAdmin = $user && $user->isAdmin();

            if (!$isOwner && !$isAdmin) {
                return $this->errorResponse(
                    'Ressource introuvable',
                    404
                );
            }
        }

        $logement->load([
            'quartier',
            'typeLogement',
            'photos',
            'equipements',
            'proprietaire',
        ]);

        return new LogementResource($logement);
    }

    /**
     * Créer un logement.
     */
    public function store(StoreLogementRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['quartier_id'] = $this->resoudreReferenceId(
            Quartier::class,
            'nom',
            $validated['quartier_id'] ?? null,
            $validated['quartier_autre'] ?? null,
            ['ville' => 'Fianarantsoa']
        );

        $validated['type_logement_id'] = $this->resoudreReferenceId(
            TypeLogement::class,
            'libelle',
            $validated['type_logement_id'] ?? null,
            $validated['type_autre'] ?? null
        );

        unset($validated['quartier_autre'], $validated['type_autre']);

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

        $logement->load([
            'quartier',
            'typeLogement',
            'equipements',
        ]);

        return $this->successResponse(
            new LogementResource($logement),
            'Logement créé avec succès',
            201
        );
    }

    /**
     * Modifier un logement.
     */
    public function update(
        UpdateLogementRequest $request,
        Logement $logement
    ): JsonResponse {
        if ($logement->proprietaire_id !== $request->user()->id) {
            return $this->errorResponse(
                'Vous ne pouvez modifier que vos propres logements',
                403
            );
        }

        $validated = $request->validated();

        if (array_key_exists('quartier_id', $validated) || array_key_exists('quartier_autre', $validated)) {
            $validated['quartier_id'] = $this->resoudreReferenceId(
                Quartier::class,
                'nom',
                $validated['quartier_id'] ?? $logement->quartier_id,
                $validated['quartier_autre'] ?? null,
                ['ville' => 'Fianarantsoa']
            );
        }

        if (array_key_exists('type_logement_id', $validated) || array_key_exists('type_autre', $validated)) {
            $validated['type_logement_id'] = $this->resoudreReferenceId(
                TypeLogement::class,
                'libelle',
                $validated['type_logement_id'] ?? $logement->type_logement_id,
                $validated['type_autre'] ?? null
            );
        }

        unset($validated['quartier_autre'], $validated['type_autre']);

        $equipements = $validated['equipements'] ?? null;

        unset($validated['equipements']);

        $logement->update($validated);

        if ($equipements !== null) {
            $logement->equipements()->sync($equipements);
        }

        $logement->load([
            'quartier',
            'typeLogement',
            'photos',
            'equipements',
        ]);

        return $this->successResponse(
            new LogementResource($logement),
            'Logement mis à jour avec succès'
        );
    }

    /**
     * Résout l'identifiant d'un référentiel (Quartier ou TypeLogement).
     * Si le texte « autre » est fourni, le nettoie (trim + espaces multiples réduits à un seul),
     * cherche une ligne existante avec le même nom en ignorant la casse (LOWER) et la réutilise ;
     * sinon crée la ligne avec est_valide = false.
     * Si pas de texte « autre », retourne l'id reçu.
     */
    private function resoudreReferenceId(
        string $modelClass,
        string $colonne,
        mixed $id,
        ?string $autre,
        array $attributsDefaut = []
    ): ?int {
        if ($autre !== null && trim($autre) !== '') {
            $texteNettoye = preg_replace('/\s+/', ' ', trim($autre));

            $existant = $modelClass::whereRaw("LOWER({$colonne}) = ?", [mb_strtolower($texteNettoye)])->first();

            if ($existant) {
                return $existant->id;
            }

            $nouvelleEntree = $modelClass::create([
                $colonne => $texteNettoye,
                'est_valide' => false,
                ...$attributsDefaut,
            ]);

            return $nouvelleEntree->id;
        }

        return $id ? (int) $id : null;
    }

    /**
     * Supprimer un logement.
     */
    public function destroy(
        Request $request,
        Logement $logement
    ): JsonResponse {
        if ($logement->proprietaire_id !== $request->user()->id) {
            return $this->errorResponse(
                'Vous ne pouvez supprimer que vos propres logements',
                403
            );
        }

        $logement->equipements()->detach();
        $logement->delete();

        return $this->successResponse(
            null,
            'Logement supprimé avec succès'
        );
    }

    /**
     * Liste des annonces du propriétaire connecté.
     */
    public function mesAnnonces(
        Request $request
    ): AnonymousResourceCollection {
        $query = $request->user()
            ->logements()
            ->with([
                'quartier',
                'typeLogement',
                'photos',
                'equipements',
            ]);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('statut_moderation')) {
            $query->where('statut_moderation', $request->statut_moderation);
        }

        $logements = $query
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request, 50));

        return LogementListResource::collection($logements);
    }
}