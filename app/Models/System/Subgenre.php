<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subgenre extends Model
{
    protected $table = '_subgenres';

    protected $fillable = [
        'name',
        'genreid',
        'is_active',
    ];

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genreid');
    }
}
