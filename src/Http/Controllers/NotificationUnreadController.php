<?php

namespace Laravel\Nova\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NotificationRequest;
use Laravel\Nova\Notifications\Notification;

class NotificationUnreadController extends Controller
{
    /**
     * Mark the given notification as unread.
     *
     * @param  \Laravel\Nova\Http\Requests\NotificationRequest  $request
     * @param  int|string  $notification
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(NotificationRequest $request, $notification)
    {
        $notification = Notification::findOrFail($notification);
        $notification->update(['read_at' => null]);

        return response()->json();
    }
}
