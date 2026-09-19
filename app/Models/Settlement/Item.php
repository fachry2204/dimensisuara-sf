<?php

namespace App\Models\Settlement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\User\Profile;

class Item extends Model
{
    protected $table = 'settlement_items';

    protected $fillable = [
        'settlementid',
        'profileid',
        'type',
        'relid',
        'description',
        'gross',
        'aggregator',
        'amount',
        'taxed',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'settlementid');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }
}
