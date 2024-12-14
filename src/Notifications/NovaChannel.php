<?php

namespace Laravel\Nova\Notifications;

use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class NovaChannel
{
    /**
     * Send channel notification.
     *
     * @param  mixed  $notifiable
     * @return void
     */
    public function send($notifiable, LaravelNotification $notification)
    {
        if (
            app()->environment('local') ||
            Gate::forUser($notifiable)->check('viewNova')
        ) {
            Notification::create([
                'id' => Str::orderedUuid(),
                'type' => get_class($notification),
                'notifiable_id' => $notifiable->getKey(),
                'notifiable_type' => $notifiable->getMorphClass(),
                'data' => $notification->toNova($notifiable), /** @phpstan-ignore method.notFound */
            ]);
        }
    }
}
