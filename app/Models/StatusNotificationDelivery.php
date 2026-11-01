<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusNotificationDelivery extends Model
{
    use \App\Models\Concerns\HasRelationalFields;

    protected $guarded = ['id'];
    protected $casts = ['payload'=>'encrypted:array','sent_at'=>'datetime'];
}
