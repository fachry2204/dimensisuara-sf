<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

use App\Models\User\Profile;
use App\Models\File;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'profileid',
        'quarter',
        'year',
        'rupiah_exact',
        'aggregator_rupiah_exact',
        'client_rupiah_exact',
        'subtotal',
        'carryover',
        'tax',
        'total',
        'status',
    ];

    protected $appends = [
        'profile_name',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'transactionid', 'id');
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
        return $this->morphOne(File::class, 'owner')->where('type', 'tax_invoice');
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

    public function getProfileNameAttribute()
    {
        return $this->profile->full_name;
    }

    public function delete()
    {
        // Delete files
        $this->slip?->delete();
        $this->tax_invoice?->delete();

        return parent::delete();
    }
}
