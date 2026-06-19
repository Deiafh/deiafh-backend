<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the permission catalog and the protected super_admin role.
     * Idempotent: safe to re-run after adding new permissions.
     */
    public function run(): void
    {
        // Clear cached roles/permissions before seeding.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'api';

        // Create every permission defined in the catalog.
        foreach (Config::get('permissions.groups') as $group) {
            foreach ($group['permissions'] as $key => $label) {
                Permission::firstOrCreate(['name' => $key, 'guard_name' => $guard]);
            }
        }

        // Super-admin holds every permission (including future ones on re-run).
        $superRole = Role::firstOrCreate([
            'name' => Config::get('permissions.super_admin_role', 'super_admin'),
            'guard_name' => $guard,
        ]);
        $superRole->syncPermissions(Permission::where('guard_name', $guard)->get());

        // Make the owner account a super-admin so nobody is locked out.
        $owner = User::where('username', 'khaled')->first();
        if ($owner && ! $owner->hasRole($superRole)) {
            $owner->assignRole($superRole);
        }
    }
}
