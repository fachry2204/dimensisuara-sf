<?php

namespace App\Models\Settlement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\User\Profile;
use App\Models\File;

class Settlement extends Model
{
    protected $table = 'settlements';

    protected $fillable = [
        'profileid',
        'type',
        'date',
        'recipient_type',
        'recipient_id',
        'quarter',
        'year',
        'gross',
        'aggregator',
        'subtotal',
        'tax',
        'tax2',
        'total',
        'taxrate',
        'taxrate2',
        'status',
        'skip_carryover_check',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'settlementid', 'id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public function slip()
    {
        return $this->morphOne(File::class, 'owner')->where('type', 'slip');
    }

    public function tax_invoice()
    {
        return $this->morphOne(File::class, 'owner')->where('type', 'tax');
    }

    public function getSlipUrlAttribute()
    {
        if($this->slip?->disk == 'local') {
            return $this->slip->url;
        }
    }

    public function getTaxInvoiceUrlAttribute()
    {
        if($this->tax_invoice?->disk == 'local') {
            return $this->tax_invoice->url;
        }
    }
}
