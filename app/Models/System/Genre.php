<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Genre extends Model
{
    protected $table = '_genres';

    protected $fillable = [
        'name',
        'is_active',
    ];

    public function subgenres(): HasMany
    {
        return $this->hasMany(Subgenre::class, 'genreid', 'id');
    }
}
