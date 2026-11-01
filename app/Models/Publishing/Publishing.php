<?php

namespace App\Models\Publishing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

use App\Models\User\Profile;
use App\Models\File;
use App\Models\Publishing\Document;
use App\Models\Contract;
use App\Models\Release;
use App\Models\Track;

class Publishing extends Model
{
    use \App\Models\Concerns\HasRelationalFields;

    protected $fillable = [
        'profileid',
        'release_id',
        'track_id',
        'songwriters',
        'title',
        'other_title',
        'sample',
        'sync_license_type',
        'performer',
        'duration_minute',
        'duration_second',
        'genre',
        'language',
        'region',
        'iswc',
        'isrc',
        'note',
        'accept_terms',

        'published_at',
        'due_date',
        'status',
        'rejectorid',
        'reason',

        'contract_expired_at',
        'contract_auto_renew',
    ];

    protected static function booted()
    {
        static::deleting(function ($publishing) {
            // Delete signee signature files BEFORE the FK cascade removes the signee rows.
            // The cascade deletes signee rows at the DB level and fires no Eloquent events,
            // so the morphOne signature File would otherwise orphan on disk.
            // File::delete() removes both the disk file and the filestorage row.
            foreach ($publishing->signees as $signee) {
                if ($signee->signature) {
                    $signee->signature->delete();
                }
            }

            // DO NOT delete documents (statement/nuon) - they are archival records
            // preserved via SET NULL even if publishing is deleted

            // Signee ROWS will be automatically deleted via CASCADE FK constraint
            // Contracts are intentionally NOT deleted (kept for historical/legal purposes)
        });
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(Signee::class, 'rejectorid');
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'release_id');
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class, 'track_id');
    }

    public function signees(): HasMany
    {
        return $this->hasMany(Signee::class, 'publishingid', 'id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'publishingid', 'id');
    }

    public function statement()
    {
        return $this->hasOne(Document::class, 'publishingid', 'id')->where('type', 'statement');
    }

    public function nuon()
    {
        return $this->hasOne(Document::class, 'publishingid', 'id')->where('type', 'nuon');
    }

    public function statementContract()
    {
        return $this->morphOne(Contract::class, 'owner')->where('type', 'publishing_statement');
    }

    public function publishingContract()
    {
        return $this->morphOne(Contract::class, 'owner')->where('type', 'publishing');
    }

    public function getRightsGrantedAttribute()
    {
        $rights = [];
        if ($this->synchronization_rights) {
            $rights[] = 'Synchronization';
        }
        if ($this->mechanical_rights) {
            $rights[] = 'Mechanical';
        }
        if ($this->performing_rights) {
            $rights[] = 'Performing';
        }
        if ($this->print_rights) {
            $rights[] = 'Printed';
        }
        if ($this->other_rights) {
            $rights[] = 'Other Rights';
        }

        return $rights;
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

    public function getlanguageNameAttribute()
    {
        if (array_key_exists('language', $this->attributes) && $this->attributes['language']) {
            $language = \Symfony\Component\Intl\Languages::getName($this->attributes['language']);
            if ($language) {
                return $language;
            }
        }

        return null;
    }
}
