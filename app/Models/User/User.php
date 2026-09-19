<?php

namespace App\Models\User;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;

use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\DB;

use App\Models\Access\Role;
use App\Models\Access\Permission;
use App\Notifications\QueuedVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use Authorizable, HasFactory, Notifiable;

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        // permission via roles
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('slug', $permission))
            ->exists();
    }

    public function getFullNameAttribute(): string
    {
        if($this->profile) {
            return $this->profile->full_name;
        }

        return $this->name;
    }
    
    public function getIsAdminAttribute(): bool
    {
        return $this->roles()->count() > 0;
    }
    
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'userid', 'id');
    }

    public function delete()
    {
        if($this->profile) {
            $this->profile->delete();
        }

        return parent::delete();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'access_user_roles', 'user_id', 'role_id');
    }

    public static function getByPermission(string|array $permissions)
    {
        // Always return an array
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        // Get permission ids
        $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->toArray();

        // Get users with roles that have the permission
        return User::whereHas('roles', fn ($q) => $q->whereHas('permissions', fn ($q) => $q->whereIn('permission_id', $permissionIds)))->get();
    }

    // public function sendEmailVerificationNotification()
    // {
    //     $this->notify(new QueuedVerifyEmail);
    // }

}
