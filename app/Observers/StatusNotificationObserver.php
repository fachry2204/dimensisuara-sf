<?php

namespace App\Observers;

use App\Models\Release;
use App\Models\User\Profile;
use App\Models\User\User;
use App\Services\StatusNotificationDispatcher;

class StatusNotificationObserver
{
    public function created($model): void
    {
        if ($model instanceof Profile) app(StatusNotificationDispatcher::class)->profile($model);
        if ($model instanceof Release) app(StatusNotificationDispatcher::class)->release($model);
    }

    public function updated($model): void
    {
        if ($model->wasChanged('status')) $this->created($model);
        if ($model instanceof User && !$model instanceof \App\Models\User\Admin && $model->wasChanged('email_verified_at') && $model->email_verified_at && $model->profile) {
            app(StatusNotificationDispatcher::class)->profile($model->profile,'user.email_verified');
        }
    }
}
