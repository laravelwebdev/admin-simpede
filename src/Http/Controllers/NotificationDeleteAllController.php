<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NotificationRequest;
use Laravel\Nova\Notifications\Notification;
use Laravel\Nova\Nova;

class NotificationDeleteAllController extends Controller
{
    /**
     * Delete all notifications.
     */
    public function __invoke(NotificationRequest $request): JsonResponse
    {
        $userId = Nova::user($request)->getKey();

        dispatch_sync(function () use ($userId) {
            Notification::whereNotifiableId($userId)->delete();
        })->afterResponse();

        return response()->json();
    }
}
