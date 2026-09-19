<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

class DatabaseChannel extends \Illuminate\Notifications\Channels\DatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        return $notifiable->routeNotificationFor('database', $notification)->create(
            $this->buildPayload($notifiable, $notification)
        );

    }

    protected function buildPayload($notifiable, Notification $notification)
    {
        return [
            // 'id' => $notification->id,
            'type' => method_exists($notification, 'databaseType')
                        ? $notification->databaseType($notifiable)
                        : get_class($notification),
            'data' => $this->getData($notifiable, $notification),
            'read_at' => null,
        ];
    }
}