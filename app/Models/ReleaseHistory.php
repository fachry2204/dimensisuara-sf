<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\User\User;

class ReleaseHistory extends Model
{
    protected $fillable = [
        'release_id',
        'action',
        'message',
        'type',
        'user_id',
        'admin_user_id',
    ];

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function user(): BelongsTo
    {
        return $this->morphTo('user', 'actor_type', 'actor_id');
    }

    public function getActorTypeAttribute(): string { return $this->admin_user_id ? \App\Models\User\Admin::class : User::class; }
    public function getActorIdAttribute(): ?int { return $this->admin_user_id ?? $this->user_id; }
}
