<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'catera:dashboard:view',
            'catera:authorized:view_any',
            'catera:authorized:create',
            'catera:authorized:update',
            'catera:authorized:delete',
            'catera:quota_scheduling:view_any',
            'catera:quota_scheduling:create',
            'catera:quota_scheduling:update',
            'catera:quota_scheduling:delete',
            'catera:access_logs:view_any',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions($permissions);

        $viewerRole = Role::findOrCreate('viewer', 'web');
        $viewerRole->syncPermissions([
            'catera:dashboard:view',
            'catera:authorized:view_any',
            'catera:quota_scheduling:view_any',
            'catera:access_logs:view_any',
        ]);
    }
}
