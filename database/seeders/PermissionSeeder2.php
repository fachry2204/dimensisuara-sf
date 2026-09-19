<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

use App\Models\Access\Permission;

class PermissionSeeder2 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $permissions = [
                [
                    'name' => 'Broadcast Message',
                    'slug' => 'broadcast',
                    'description' => 'Broadcast a message to all/selected users',
                    'category'  => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Publishing',
                    'slug' => 'view-publishing',
                    'description' => 'View publishing and download publishing files',
                    'category'  => 'publishing',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Create Publishing',
                    'slug' => 'create-publishing',
                    'description' => 'Create publishing for users',
                    'category'  => 'publishing',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manage Publishing Status',
                    'slug' => 'manage-publishing-status',
                    'description' => 'Manage status of created publishing',
                    'category'  => 'publishing',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Edit Publishing',
                    'slug' => 'edit-publishing',
                    'description' => 'Edit publishing',
                    'category'  => 'publishing',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Delete Publishing',
                    'slug' => 'delete-publishing',
                    'description' => 'Delete publishing',
                    'category'  => 'publishing',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View MCN',
                    'slug' => 'view-mcn',
                    'description' => 'View MCN and download MCN files',
                    'category'  => 'mcn',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Create MCN',
                    'slug' => 'create-mcn',
                    'description' => 'Create MCN for users',
                    'category'  => 'mcn',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manage MCN Status',
                    'slug' => 'manage-mcn-status',
                    'description' => 'Manage status of created MCN',
                    'category'  => 'mcn',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Edit MCN',
                    'slug' => 'edit-mcn',
                    'description' => 'Edit MCN',
                    'category'  => 'mcn',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Delete MCN',
                    'slug' => 'delete-mcn',
                    'description' => 'Delete MCN',
                    'category'  => 'mcn',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                
            ];
            
            Permission::insert($permissions);
        });
    }
}
