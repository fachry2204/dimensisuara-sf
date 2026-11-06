<?php

namespace App\Services;

use App\Models\User\Admin;
use Illuminate\Notifications\Events\NotificationSending;

class AdminNotificationPreferences
{
    public function handle(NotificationSending $event): ?bool
    {
        if (!$event->notifiable instanceof Admin) return null;
        // Read at delivery time as preferences may change after a job was queued.
        $admin = Admin::find($event->notifiable->getKey());
        if (!$admin || !$admin->allowsNotificationChannel($event->channel)) return false;
        $event->notifiable->notification_phone = $admin->notification_phone;
        return null;
    }
}
