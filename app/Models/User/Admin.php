<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admin extends User
{
    protected $table = 'user_admin';

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['email_notifications_enabled'=>'boolean', 'whatsapp_notifications_enabled'=>'boolean']);
    }

    public function allowsNotificationChannel(string $channel): bool
    {
        return match ($channel) {
            'mail' => $this->email_notifications_enabled ?? true,
            \App\Services\WhatsAppService::class, 'whatsapp' => $this->whatsapp_notifications_enabled ?? true,
            default => true,
        };
    }

    protected static function newFactory()
    {
        return \Database\Factories\AdminFactory::new();
    }

    public function profile(): HasOne
    {
        // Account IDs belong to independent tables and may overlap.
        return parent::profile()->whereRaw('1 = 0');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Access\Role::class, 'access_user_roles', 'user_id', 'role_id');
    }
}
