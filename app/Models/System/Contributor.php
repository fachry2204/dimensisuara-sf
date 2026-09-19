<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class Contributor extends Model
{
    protected $table = '_contributors';

    protected $fillable = [
        'name',
        'is_instrumental',
        'is_active',
    ];

    protected $casts = [
        'is_instrumental' => 'boolean',
        'is_active' => 'boolean',
    ];
}
