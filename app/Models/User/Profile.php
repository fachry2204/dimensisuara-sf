<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Support\Facades\DB;

use App\Models\Contract;
use App\Models\File;
use App\Models\Publishing\Publishing;
use App\Models\Publishing\Songwriter;
use App\Models\Release;
use App\Models\Track;

class Profile extends Model
{
    protected $table = 'user_profiles';

    protected $fillable = [
        'old_id',
        'firstname',
        'lastname',
        'phone',

        'is_company',
        'companyname',
        'position',

        'address1',
        'address2',

        'city',
        'state',
        'provinceid',
        'cityid',
        'districtid',
        'villageid',
        
        'zip',
        'country',

        'bankname',
        'bankbranch',
        'accountname',
        'accountnumber',

        'feature_aggregator',
        'feature_publishing',
        'feature_mcn',

        'dir_root',
        'dir_identity',
        'dir_aggregator',
        'dir_publishing',
        'dir_mcn',
        'dir_release',

        'ktp_num',
        'npwppersonal_num',
        'nibcompany_num',
        'npwpcompany_num',

        'aggregator_rate',
        'mcn_rate',
        'publishing_rate',

        'status',
        'reason',
        'delete_by',
    ];

    protected $appends = ['full_name', 'full_address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class, 'profileid', 'id');
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class, 'profileid', 'id');
    }

    public function identities()
    {
        return $this->morphMany(File::class, 'owner');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'profileid', 'id');
    }

    public function aggregator_contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'profileid', 'id')->where('type', 'aggregator');
    }

    public function publishing_contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'profileid', 'id')->where('type', 'publishing');
    }

    public function mcn_contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'profileid', 'id')->where('type', 'mcn');
    }

    public function publishings(): HasMany
    {
        return $this->hasMany(Publishing::class, 'profileid', 'id');
    }
    
    public function songwriters(): HasMany
    {
        return $this->hasMany(Songwriter::class, 'profileid', 'id');
    }

    public function getFullNameAttribute(): string
    {
        if($this->lastname) {
            return $this->firstname . ' ' . $this->lastname;
        }

        return $this->firstname;
    }

    public function getFullAddressAttribute(): string
    {
        // Generate clean address
        $imploded = $values = [];

        // Get address 1 and 2
        $address = isset($this->address2) ? $this->address1 . ', ' . $this->address2 : $this->address1;

        // If country is Indonesia and provinceid is set (using new address format)
        if($this->country == 'ID' && $this->provinceid) {
            $values = [$address, $this->kelurahan, $this->kecamatan, $this->kota, $this->provinsi, $this->zip, $this->country_name];
        }
        else {
            $values = [$address, $this->city, $this->state, $this->zip, $this->country_name];
        }

        foreach($values as $value) {
            if($value && trim($value) !== '' && trim($value) !== '-') {
                $imploded[] = $value;
            }
        }

        // Old address format
        return implode(', ', $imploded);
    }

    public function getCountryNameAttribute()
    {
        if($this->country) {
            return \Monarobase\CountryList\CountryListFacade::getOne($this->country);
        }

        return null;
    }

    public function getKelurahanAttribute()
    {
        if($this->villageid) {
            return DB::table('wilayah')->where('kode',$this->villageid)->get()->first()->nama;
        }
    }

    public function getKecamatanAttribute()
    {
        if($this->districtid) {
            return DB::table('wilayah')->where('kode',$this->districtid)->get()->first()->nama;
        }
    }

    public function getKotaAttribute()
    {
        if($this->cityid) {
            return DB::table('wilayah')->where('kode',$this->cityid)->get()->first()->nama;
        }
    }

    public function getProvinsiAttribute()
    {
        if($this->provinceid) {
            return DB::table('wilayah')->where('kode',$this->provinceid)->get()->first()->nama;
        }
    }

    public function delete()
    {
        // Contracts are CASCADE deleted by database FK
        // Publishings & Songwriters are CASCADE deleted by database FK

        // Delete releases (also deletes their files via Release.delete())
        foreach ($this->releases as $release) {
            $release->delete();
        }

        // Delete tracks (also deletes their files via Track.delete())
        foreach ($this->tracks as $track) {
            $track->delete();
        }

        // Delete identities (files)
        foreach ($this->identities as $identity) {
            $identity->delete();
        }

        return parent::delete();
    }
}
