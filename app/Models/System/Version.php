<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $table = '_versions';

    protected $fillable = [
        'name',
        'is_active',
    ];
}
