<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Support\Facades\DB;

use App\Models\User\Profile;
use App\Models\File;
use App\Enums\ReleaseStatus;

class Release extends Model
{
    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, \App\Models\User\User $viewer): \Illuminate\Database\Eloquent\Builder
    {
        return $viewer->can('view-releases-all') ? $query : $query->where('releases.status', 'released');
    }

    protected $casts = [
        'releaseddate' => 'date',
        'finalreleaseddate' => 'date',
    ];

    protected $fillable = [
        'profileid',
        'old_album_id',
        'old_track_id',
        'type',
        'tracknum',
        'title',
        'titlelang',
        'primaryartists',
        'recordlabel',
        'version',
        'genre',
        'subgenre',
        'releasedir',
        'released',
        'releaseddate',
        'finalreleaseddate',
        'upc',
        'aggregator',
        'status',
        'reason',
        'previous_rejected_status',
        'releaseon',
        'tiktok_ytmusic_rerelease_date',
        'delete_by',
        'verified_at',
        'contract_expired_at',
        'contract_auto_renew',
    ];

    protected $hidden = [
        'profileid',
        'updated_at',
        'releasedir',
    ];

    protected $appends = [
        'cover_url',
        'ipl_url',
        'thumbnail_url',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'release_track', 'release_id', 'track_id')
            ->withPivot('track_order')
            ->withTimestamps()
            ->orderBy('track_order');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ReleaseHistory::class, 'release_id', 'id')->latest();
    }

    public function cover()
    {
        return $this->morphOne(File::class, 'owner')->where('type', 'cover');
    }

    public function ipl()
    {
        return $this->morphOne(File::class, 'owner')->where('type', 'ipl');
    }

    public function thumbnail()
    {
        return $this->morphOne(File::class, 'owner')->where('type', 'thumbnail');
    }

    public function getCoverUrlAttribute()
    {
        return $this->cover ? $this->cover->url : null;
    }

    public function getIplUrlAttribute()
    {
        return $this->ipl ? $this->ipl->url : null;
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? $this->thumbnail->url : null;
    }

    public function getVersionNameAttribute()
    {
        if (array_key_exists('version', $this->attributes)) {
            $version = DB::table('_versions')->where('id', $this->attributes['version'])->first();
            if ($version) {
                return $version->name;
            }
        }

        return null;
    }

    public function getGenreNameAttribute()
    {
        if (array_key_exists('genre', $this->attributes)) {
            $genre = DB::table('_genres')->where('id', $this->attributes['genre'])->first();
            if ($genre) {
                return $genre->name;
            }
        }

        return null;
    }

    public function getSubgenreNameAttribute()
    {
        if (array_key_exists('subgenre', $this->attributes)) {
            $subgenre = DB::table('_subgenres')->where('id', $this->attributes['subgenre'])->first();
            if ($subgenre) {
                return $subgenre->name;
            }
        }

        return null;
    }

    public function getTitlelangNameAttribute()
    {
        if (array_key_exists('titlelang', $this->attributes)) {
            if ($this->attributes['titlelang']) {
                $titlelang = \Symfony\Component\Intl\Languages::getName($this->attributes['titlelang']);
                if ($titlelang) {
                    return $titlelang;
                }
            }
        }

        return null;
    }

    public function getPrimaryartistsAttribute()
    {
        if (array_key_exists('primaryartists', $this->attributes)) {
            return json_decode($this->attributes['primaryartists']);
        }

        return null;
    }

    public function getHasCoverAttribute()
    {
        return $this->file || $this->draft;
    }

    public function delete()
    {
        // Delete files
        $this->cover?->delete();
        $this->thumbnail?->delete();
        $this->ipl?->delete();

        // Delete tracks
        foreach ($this->tracks as $track) {
            $track->delete();
        }

        return parent::delete();
    }

}
