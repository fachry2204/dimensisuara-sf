<?php

namespace App\Models;

use App\Models\User\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $table = 'filestorage';

    protected $fillable = [
        'disk',
        'identifier',
        'backup_identifier',
        'backed_up_at',
        'backup_attempts',
        'backup_error',
        'original_name',
        'mime_type',
        'size',
        'uploaded_at',
        'type',
        'owner_id',
        'owner_type',
        'profileid',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public function delete()
    {
        // Delete file
        if ($this->identifier) {
            switch ($this->disk) {
                case 'local':
                    Storage::disk('local')->delete($this->identifier);
                    break;
                case 'google':
                    break;
            }
        }

        // Delete backup
        if ($this->backup_identifier) {
            Storage::disk('ftp')->delete($this->backup_identifier);
        }

        return parent::delete();
    }

    public function getUrlAttribute()
    {
        // Signed route to secure download/streaming controller
        return route('file.access', [
            'file' => $this->id,
            'hash' => sha1($this->id.'|'.$this->updated_at),
        ]);
    }

    public function getUrlAsSigneeAttribute()
    {
        // Signed route to secure download/streaming controller
        return route('file.signee', [
            'file' => $this->id,
            'token' => 123,
            'hash' => sha1($this->id.'|'.$this->updated_at),
        ]);
    }
}
