<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\User\User;

class LogActivity extends Model
{
    use \App\Models\Concerns\HasRelationalFields;

    protected $table = 'logs';

    public function user(): BelongsTo
    {
        return $this->morphTo('user', 'actor_type', 'actor_id');
    }

    public function getActorTypeAttribute(): string { return $this->admin_user_id ? \App\Models\User\Admin::class : User::class; }
    public function getActorIdAttribute(): ?int { return $this->admin_user_id ?? $this->userid; }
}
