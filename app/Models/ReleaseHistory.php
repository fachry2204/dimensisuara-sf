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
    ];

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
