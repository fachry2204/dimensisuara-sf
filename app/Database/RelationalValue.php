<?php

namespace App\Database;

use Illuminate\Database\Eloquent\Model;

class RelationalValue extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
}
