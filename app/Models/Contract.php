<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use App\Models\User\Profile;

use App\Services\ContractService;

class Contract extends Model
{
    protected $casts = ['generated_html' => 'encrypted', 'user_percentage' => 'integer', 'system_percentage' => 'integer', 'contract_date' => 'date'];
    protected $hidden = ['generated_html'];

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
        'contract_date',
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
        return $this->morphOne(File::class, 'owner')->where(function ($query) {
            $query->whereNull('type')->orWhere('type', '!=', 'digital_stamp');
        });
    }

    public function digitalStamp()
    {
        return $this->morphOne(File::class, 'owner')->where('type', 'digital_stamp');
    }

    public function delete()
    {
        $this->file?->delete();
        $this->digitalStamp?->delete();
        return parent::delete();
    }
}
