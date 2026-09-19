<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Relationships\BelongsToMany;

use App\Models\Access\Role;

class Permission extends Model
{
    protected $table = 'access_permissions';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'access_role_permissions');
    }
}
