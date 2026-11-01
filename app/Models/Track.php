<?php

namespace App\Models;

use App\Models\User\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Track extends Model
{
    use \App\Models\Concerns\HasRelationalFields;

    public function scopeInReleaseCatalogue(\Illuminate\Database\Eloquent\Builder $query, \App\Models\User\User $viewer): \Illuminate\Database\Eloquent\Builder
    {
        // Count each track once, only while it belongs to an accessible release.
        return $query->whereHas('releases', fn ($releases) => $releases->visibleTo($viewer));
    }
    protected $fillable = [
        'order',
        'profileid',
        'releaseid',
        'title',
        'titlelang',
        'version',
        'genre',
        'subgenre',
        'primaryartists',
        'featuredartists',
        'contributors',
        'productions',
        'instrumental',
        'lyrics',
        'lyriclang',
        'explicit',
        'isrc',
        'duration',
        'iswc',
        'writer_percentage',
        'songwriters',
    ];

    protected $casts = [
        'songwriters' => 'array',
    ];

    protected $hidden = [
        'full_file',
        'trimmed_file',
    ];

    protected $appends = [
        'release_id',
        'release_cover',
        'full_exists',
        'trimmed_exists',
        'full_url',
        'trimmed_url',
        'full_name',
        'trimmed_name',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public function releases(): BelongsToMany
    {
        return $this->belongsToMany(Release::class, 'release_track', 'track_id', 'release_id')
            ->withPivot('upc', 'isrc', 'track_order')
            ->withTimestamps()
            ->orderBy('track_order');
    }

    public function full_file()
    {
        return $this->morphOne(File::class, 'owner')->where('type', 'full');
    }

    public function trimmed_file()
    {
        return $this->morphOne(File::class, 'owner')->where('type', 'trimmed');
    }

    public function getFullUrlAttribute()
    {
        return $this->full_file ? $this->full_file->url : null;
    }

    public function getTrimmedUrlAttribute()
    {
        return $this->trimmed_file ? $this->trimmed_file->url : null;
    }

    public function getFullNameAttribute()
    {
        return $this->full_file ? $this->full_file->original_name : null;
    }

    public function getTrimmedNameAttribute()
    {
        return $this->trimmed_file ? $this->trimmed_file->original_name : null;
    }

    public function getFullExistsAttribute()
    {
        if ($this->full_file || $this->full_draft) {
            return true;
        }

        return null;
    }

    public function getTrimmedExistsAttribute()
    {
        if ($this->trimmed_file || $this->trimmed_draft) {
            return true;
        }

        return null;
    }

    public function getReleaseIdAttribute()
    {
        return $this->releases()->orderBy('created_at', 'desc')->first()->id;
    }

    public function getReleaseCoverAttribute()
    {
        return $this->releases()->orderBy('created_at', 'desc')->first()->cover_url;
    }

    public function getVersionNameAttribute()
    {
        $version = DB::table('_versions')->where('id', $this->attributes['version'])->first();
        if ($version) {
            return $version->name;
        }

        return $this->attributes['version'];
    }

    public function getGenreNameAttribute()
    {
        $genre = DB::table('_genres')->where('id', $this->attributes['genre'])->first();
        if ($genre) {
            return $genre->name;
        }

        return $this->attributes['genre'];
    }

    public function getSubgenreNameAttribute()
    {
        $subgenre = DB::table('_subgenres')->where('id', $this->attributes['subgenre'])->first();
        if ($subgenre) {
            return $subgenre->name;
        }

        return $this->attributes['subgenre'];
    }

    public function getTitlelangNameAttribute()
    {
        if ($this->attributes['titlelang']) {
            $titlelang = \Symfony\Component\Intl\Languages::getName($this->attributes['titlelang']);
            if ($titlelang) {
                return $titlelang;
            }
        }

        return $this->attributes['titlelang'];
    }

    public function getExplicitNameAttribute()
    {
        switch ($this->attributes['explicit']) {
            case 'non-explicit':
                return 'Non-Explicit';
            case 'explicit':
                return 'Explicit';
            case 'clean':
                return 'Clean';
            default:
                return $this->attributes['explicit'];
        }
    }

    public function getlyriclangNameAttribute()
    {
        if ($this->attributes['lyriclang']) {
            $lyriclang = \Symfony\Component\Intl\Languages::getName($this->attributes['lyriclang']);
            if ($lyriclang) {
                return $lyriclang;
            }
        }

        return $this->attributes['lyriclang'];
    }

    public function getPrimaryartistsAttribute()
    {
        if (! $this->attributes['primaryartists']) {
            return [];
        }

        return json_decode($this->attributes['primaryartists']);
    }

    public function getFeaturedartistsAttribute()
    {
        if (! $this->attributes['featuredartists']) {
            return [];
        }

        return json_decode($this->attributes['featuredartists']);
    }

    public function getContributorsAttribute()
    {
        if (! $this->attributes['contributors']) {
            return [];
        }

        return json_decode($this->attributes['contributors']);
    }

    public function getProductionsAttribute()
    {
        if (! $this->attributes['productions']) {
            return [];
        }

        return json_decode($this->attributes['productions']);
    }

    public function getContributorsPrettyAttribute()
    {
        if ($this->contributors) {
            $roles = DB::table('_contributors')->pluck('name', 'id')->toArray();

            $contributors = $this->contributors;
            $pretty = [];
            foreach ($contributors as $contributor) {
                $pretty[] = (object) [
                    'name' => $contributor->name,
                    'role' => $roles[$contributor->role],
                ];
            }

            return $pretty;
        }

        return [];
    }

    public function getProductionsPrettyAttribute()
    {
        if ($this->productions) {
            $roles = DB::table('_productions')->pluck('name', 'id')->toArray();

            $productions = $this->productions;
            $pretty = [];
            foreach ($productions as $production) {
                $pretty[] = (object) [
                    'name' => $production->name,
                    'role' => $roles[$production->role],
                ];
            }

            return $pretty;
        }

        return [];
    }

    public function getViewsAttribute()
    {
        return DB::table('report_aggregate_tracks')->where('trackid', $this->attributes['id'])->sum('quantity');
    }

    public function delete()
    {
        // Shared recordings are removed only after their final release is detached.
        foreach (File::where('owner_type', $this->getMorphClass())->where('owner_id', $this->id)->get() as $file) {
            $file->delete();
        }

        // Delete track
        return parent::delete();
    }
}
