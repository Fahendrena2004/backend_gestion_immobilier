<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Modules\Notifications\Models\Notification;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('lu', false)
            ->count();

        return response()->json([
            'success'      => true,
            'message'      => 'Notifications récupérées avec succès',
            'data'         => $notifications->items(),
            'unread_count' => $unreadCount,
            'meta'         => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $notification = Notification::find($id);

        if (!$notification) {
            return $this->errorResponse('Notification introuvable.', 404);
        }

        if ($notification->user_id !== $user->id) {
            return $this->errorResponse(
                'Vous n\'avez pas les droits pour accéder à cette notification.',
                403
            );
        }

        if ($notification->lu) {
            return $this->successResponse($notification, 'Notification déjà marquée comme lue');
        }

        $notification->update(['lu' => true]);

        return $this->successResponse($notification->fresh(), 'Notification marquée comme lue');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $updated = Notification::where('user_id', $user->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return $this->successResponse(
            ['updated' => $updated],
            "{$updated} notification(s) marquée(s) comme lue(s)"
        );
    }
}
