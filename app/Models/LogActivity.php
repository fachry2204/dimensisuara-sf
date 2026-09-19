<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\User\User;

class LogActivity extends Model
{
    protected $table = 'logs';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
