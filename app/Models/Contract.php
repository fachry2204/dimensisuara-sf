<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use App\Models\User\Profile;

use App\Services\ContractService;

class Contract extends Model
{
    protected $fillable = [
        'type',
        'year',
        'month',
        'number',
        'docnum',
        'owner_type',
        'owner_id',
        'profileid',
        'sort',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function file()
    {
        return $this->morphOne(File::class, 'owner');
    }

    public function delete()
    {
        $this->file?->delete();
        return parent::delete();
    }
}
