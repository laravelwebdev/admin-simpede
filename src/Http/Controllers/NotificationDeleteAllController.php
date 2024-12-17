<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NotificationRequest;
use Laravel\Nova\Notifications\Notification;
use Laravel\Nova\Nova;

class NotificationDeleteAllController extends Controller
{
    /**
     * Delete all notifications.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(NotificationRequest $request)
    {
        Notification::whereNotifiableId(Nova::user($request)->getKey())->delete();

        return response()->json();
    }
}
