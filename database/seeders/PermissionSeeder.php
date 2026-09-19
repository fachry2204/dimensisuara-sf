<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Access\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $permissions = [
                [
                    'name' => 'Manage Automation',
                    'slug' => 'manage-automation',
                    'description' => 'See jobs, batches, failures, and rerun jobs',
                    'category'  => 'automation',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manage Admins',
                    'slug' => 'manage-admins',
                    'description' => 'Create, edit, and delete admins',
                    'category'  => 'rolespermissions',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manage Roles & Permissions',
                    'slug' => 'manage-roles',
                    'description' => 'Create role and user assignments, manage permissions, and delete roles',
                    'category'  => 'rolespermissions',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Roles & Permissions',
                    'slug' => 'view-roles',
                    'description' => 'View role and user assignments',
                    'category'  => 'rolespermissions',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Logging',
                    'slug' => 'view-logging',
                    'description' => 'View logs of all system activities',
                    'category'  => 'logging',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Generate Contracts',
                    'slug' => 'generate-contracts',
                    'description' => 'Generate contracts for users (e.g. aggregator, publishing, and MCN)',
                    'category'  => 'contract',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Upload Contracts',
                    'slug' => 'upload-contracts',
                    'description' => 'Upload contracts file',
                    'category'  => 'contract',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Contracts',
                    'slug' => 'view-contracts',
                    'description' => 'View and download created or uploaded contracts',
                    'category'  => 'contract',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Delete Contracts',
                    'slug' => 'delete-contracts',
                    'description' => 'Delete created contracts',
                    'category'  => 'contract',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Create Users',
                    'slug' => 'create-users',
                    'description' => 'Create users',
                    'category'  => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manage User Status',
                    'slug' => 'manage-user-status',
                    'description' => 'Manage status of users',
                    'category'  => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manage User Features',
                    'slug' => 'manage-user-features',
                    'description' => 'Manage features of users (e.g. allow aggregator, publishing, and MCN)',
                    'category'  => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Impersonate Users',
                    'slug' => 'impersonate-users',
                    'description' => 'Open dashboard and take action as user',
                    'category'  => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Users',
                    'slug' => 'view-users',
                    'description' => 'View users list and details',
                    'category'  => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Edit Users',
                    'slug' => 'edit-users',
                    'description' => 'Edit users details',
                    'category'  => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Delete Users',
                    'slug' => 'delete-users',
                    'description' => 'Delete users',
                    'category'  => 'users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Releases',
                    'slug' => 'view-releases',
                    'description' => 'View releases and download releases files',
                    'category'  => 'aggregator',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Create Releases',
                    'slug' => 'create-releases',
                    'description' => 'Create releases for users',
                    'category'  => 'aggregator',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manage Release Status',
                    'slug' => 'manage-releases-status',
                    'description' => 'Manage status of created releases',
                    'category'  => 'aggregator',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View All Releases',
                    'slug' => 'view-releases-all',
                    'description' => 'View releases of any status (without this, only published releases are visible)',
                    'category'  => 'aggregator',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Download Release Files',
                    'slug' => 'download-release-files',
                    'description' => 'Download release album cover and track audio files',
                    'category'  => 'aggregator',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Edit Releases',
                    'slug' => 'edit-releases',
                    'description' => 'Edit releases',
                    'category'  => 'aggregator',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Delete Releases',
                    'slug' => 'delete-releases',
                    'description' => 'Delete releases',
                    'category'  => 'aggregator',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Transactions',
                    'slug' => 'view-transactions',
                    'description' => 'View transactions',
                    'category'  => 'transaction',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Generate Transactions',
                    'slug' => 'generate-transactions',
                    'description' => 'Generate transactions based on uploaded reporting',
                    'category'  => 'transaction',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manage Generated Transactions',
                    'slug' => 'manage-generated-transactions',
                    'description' => 'Allow upload transfer receipts and tax invoices',
                    'category'  => 'transaction',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Delete Transactions',
                    'slug' => 'delete-transactions',
                    'description' => 'Delete generated transactions',
                    'category'  => 'transaction',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Reporting',
                    'slug' => 'view-reporting',
                    'description' => 'View uploaded reports and revenues',
                    'category'  => 'reporting',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Upload Reporting',
                    'slug' => 'upload-reporting',
                    'description' => 'Upload and finalize reporting, and recheck incomplete reports',
                    'category'  => 'reporting',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Delete Reporting',
                    'slug' => 'delete-reporting',
                    'description' => 'Delete uploaded reports',
                    'category'  => 'reporting',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'View Financial Menu',
                    'slug' => 'view-finance',
                    'description' => 'View Financial menu',
                    'category'  => 'finance',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            
            Permission::insert($permissions);
        });
    }
}
