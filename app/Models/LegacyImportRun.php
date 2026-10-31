<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyImportRun extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['stats' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
}
