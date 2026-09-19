<?php

use Illuminate\Database\Migrations\Migration;

use App\Models\Access\Permission;
use App\Models\Access\Role;

/**
 * Emergency: restrict the Partner role in the releases area.
 *
 * Introduces two additive permissions and grants them to the roles that
 * currently hold `view-releases` EXCEPT Partner. Absence of these permissions
 * (i.e. Partner) means: only published releases are visible, and release
 * cover/track files cannot be downloaded.
 */
return new class extends Migration
{
    private array $permissions = [
        [
            'name'        => 'View All Releases',
            'slug'        => 'view-releases-all',
            'description' => 'View releases of any status (without this, only published releases are visible)',
            'category'    => 'aggregator',
        ],
        [
            'name'        => 'Download Release Files',
            'slug'        => 'download-release-files',
            'description' => 'Download release album cover and track audio files',
            'category'    => 'aggregator',
        ],
    ];

    // Roles that keep full release access (everyone with view-releases except Partner).
    private array $grantTo = ['Super Admin', 'Operator'];

    public function up(): void
    {
        $permissionIds = [];

        foreach ($this->permissions as $permission) {
            $model = Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name'        => $permission['name'],
                    'description' => $permission['description'],
                    'category'    => $permission['category'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );

            $permissionIds[] = $model->id;
        }

        foreach ($this->grantTo as $roleName) {
            $role = Role::where('name', $roleName)->first();

            // syncWithoutDetaching is idempotent — safe to re-run.
            $role?->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    public function down(): void
    {
        $slugs = array_column($this->permissions, 'slug');
        $permissions = Permission::whereIn('slug', $slugs)->get();

        foreach ($permissions as $permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};
