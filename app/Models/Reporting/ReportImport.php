<?php

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

use App\Models\User\User;

class ReportImport extends Model
{
    protected $fillable = [
        'userid',
        'source',
        'original_filename',
        'sheet',
        'idr_rate',
        'status',
        'batch_id',
        'total_rows',
    ];

    protected  $appends = ['uploader'];

    public function rows(): HasMany
    {
        return $this->hasMany(ReportRow::class, 'report_import_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid');
    }

    public function getUploaderAttribute()
    {
        return $this->user->full_name;
    }
}
