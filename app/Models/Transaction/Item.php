<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Model;

use App\Models\User\Profile;

class Item extends Model
{
    protected $table = 'transaction_items';

    protected $fillable = [
        'profileid',
        'transactionid',
        'month',
        'amount'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(transaction::class, 'transactionid');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }
}
