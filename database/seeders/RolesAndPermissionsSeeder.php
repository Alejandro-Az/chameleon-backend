<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'api';

        // 1. Crear permisos
        $corePermissions = [
            'admin.users.manage',
            'admin.roles.manage',
            'admin.permissions.manage',
            'admin.audit.view',
            'admin.security.view',
            'admin.service_accounts.manage',
            'admin.api_keys.manage',
            'admin.policies.view',
            'admin.policies.manage',
        ];

        $appointmentsPermissions = [
            'appointments.services.manage',
            'appointments.staff.manage',
            'appointments.availability.manage',
            'appointments.bookings.view_all',
            'appointments.bookings.view_own',
            'appointments.bookings.create_internal',
            'appointments.bookings.manage',
            'appointments.bookings.assign',
            'appointments.bookings.status.manage',
        ];

        $permissions = $corePermissions;
        if ((bool) config('kaan.features.appointments', false)) {
            $permissions = array_merge($permissions, $appointmentsPermissions);
        }

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => $guard]);
        }

        // 2. Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $masterRole = Role::firstOrCreate(['name' => 'master', 'guard_name' => $guard]);
        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => $guard]);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => $guard]);

        // Sincronizar permisos del admin (esto cubre nuevos permisos si el rol ya existía)
        $adminRole->syncPermissions($permissions);

        if ((bool) config('kaan.features.appointments', false)) {
            $masterRole->syncPermissions($appointmentsPermissions);
            $employeeRole->syncPermissions([
                'appointments.bookings.view_own',
            ]);
        }
    }
}
