<?php

namespace App\Models\Publishing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\User\Profile;
use App\Models\Publishing\Publishing;
use App\Models\File;

class Document extends Model
{
    protected $table = 'publishing_documents';

    protected $fillable = [
        'publishingid',
        'profileid',
        'type',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
    ];

    public function publishing(): BelongsTo
    {
        return $this->belongsTo(Publishing::class, 'publishingid');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public function file()
    {
        return $this->morphOne(File::class, 'owner');
    }

    /**
     * Override delete to preserve archival files
     * Documents are archival - files should never be deleted
     */
    public function delete()
    {
        // DO NOT delete the file - it's an archival document
        // Only delete the document record itself
        return parent::delete();
    }
}
