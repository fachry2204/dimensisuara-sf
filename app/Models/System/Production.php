<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $table = '_productions';

    protected $fillable = [
        'name',
        'is_active',
    ];
}
