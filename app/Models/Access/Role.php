<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\Access\Permission;
use App\Models\User\Admin;

class Role extends Model
{
    protected $table = 'access_roles';

    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'access_user_roles', 'role_id', 'user_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'access_role_permissions', 'role_id', 'permission_id');
    }

}
