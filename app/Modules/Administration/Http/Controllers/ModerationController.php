<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Models\User;
use App\Modules\Administration\Http\Requests\ModerateLogementRequest;
use App\Modules\Administration\Http\Requests\UpdateUserStatusRequest;
use App\Modules\Logements\Models\Logement;
use App\Modules\Notifications\Models\Notification;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController
{
    use ApiResponseTrait;

    public function annonces(Request $request): JsonResponse
    {
        $query = Logement::with(['quartier', 'typeLogement', 'proprietaire', 'photos']);

        if ($request->filled('statut_moderation')) {
            $query->where('statut_moderation', $request->statut_moderation);
        }

        $logements = $query->orderByDesc('created_at')->paginate(15);

        return $this->successResponse($logements);
    }

    public function moderateLogement(ModerateLogementRequest $request, int $id): JsonResponse
    {
        $logement = Logement::findOrFail($id);

        $logement->update([
            'statut_moderation' => $request->statut_moderation,
        ]);

        $titre = match ($request->statut_moderation) {
            'approuve' => 'Logement approuvé',
            'suspendu' => 'Logement suspendu',
            'supprime' => 'Logement supprimé',
            default    => 'Statut du logement modifié',
        };

        Notification::create([
            'user_id' => $logement->proprietaire_id,
            'titre'   => $titre,
            'contenu' => "Votre logement \"{$logement->titre}\" a été modéré. Nouveau statut : {$request->statut_moderation}.",
            'type'    => 'moderation_logement',
        ]);

        return $this->successResponse($logement->fresh(), 'Logement modéré avec succès');
    }

    public function updateUserStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        /** @var User $admin */
        $admin = $request->user();

        if ($admin->id === $id) {
            return $this->errorResponse('Vous ne pouvez pas désactiver votre propre compte.', 403);
        }

        $target = User::findOrFail($id);

        if ($target->isAdmin()) {
            return $this->errorResponse('Impossible de modifier le statut d\'un autre administrateur.', 403);
        }

        if (!$request->is_active) {
            $target->tokens()->delete();
        }

        $target->update([
            'is_active' => $request->is_active,
        ]);

        $message = $request->is_active
            ? 'Compte utilisateur réactivé avec succès'
            : 'Compte utilisateur désactivé avec succès';

        return $this->successResponse($target->fresh(), $message);
    }
}
