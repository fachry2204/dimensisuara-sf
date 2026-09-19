<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Access\Permission;
use App\Models\Access\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $roles = [
                [
                    'name' => 'Super Admin',
                    'description' => 'Allow all access',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'permissions' => [
                        'manage-automation',
                        'manage-roles',
                        'manage-admins',
                        'view-roles',
                        'view-logging',
                        'generate-contracts',
                        'upload-contracts',
                        'view-contracts',
                        'delete-contracts',
                        'create-users',
                        'manage-user-status',
                        'manage-user-features',
                        'impersonate-users',
                        'view-users',
                        'edit-users',
                        'delete-users',
                        'view-releases',
                        'view-releases-all',
                        'download-release-files',
                        'create-releases',
                        'manage-releases-status',
                        'edit-releases',
                        'delete-releases',
                        'view-transactions',
                        'generate-transactions',
                        'manage-generated-transactions',
                        'delete-transactions',
                        'view-reporting',
                        'upload-reporting',
                        'delete-reporting',
                        'view-finance',
                    ]
                ],
                [
                    'name' => 'Operator',
                    'description' => 'Manage releases, contract, and report',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'permissions' => [
                        'generate-contracts',
                        'upload-contracts',
                        'view-contracts',
                        'create-users',
                        'manage-user-status',
                        'manage-user-features',
                        'impersonate-users',
                        'view-users',
                        'edit-users',
                        'view-releases',
                        'view-releases-all',
                        'download-release-files',
                        'create-releases',
                        'manage-releases-status',
                        'edit-releases',
                        'view-reporting',
                        'upload-reporting',
                    ]
                ],
                [
                    'name' => 'Partner',
                    'description' => 'View releases',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'permissions' => [
                        'view-releases',
                    ]
                ],
                [
                    'name' => 'Finance',
                    'description' => 'Manage transaction, and report',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'permissions' => [
                        'view-transactions',
                        'generate-transactions',
                        'manage-generated-transactions',
                        'delete-transactions',
                        'view-reporting',
                        'upload-reporting',
                        'delete-reporting',
                        'view-finance',
                    ]
                ],
            ];

            foreach ($roles as $role) {
                $newRole = Role::create([
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'created_at' => $role['created_at'],
                    'updated_at' => $role['updated_at'],
                ]);

                foreach ($role['permissions'] as $permission_slug) {
                    $permission = Permission::where('slug', $permission_slug)->first();
                    $newRole->permissions()->attach($permission);
                }
            }
        });
    }
}
