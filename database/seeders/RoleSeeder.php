<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'sanctum';

        $roles = ['Client', 'Lead Account Specialist', 'Account Specialist', 'Marketing', 'Human Resource', 'Lead Operations','Operations', 'Lead Finance', 'Finance', 'IT', 'Client Success', 'Lead Client Success'];
        $permissions = [
            'dashboard.view',
            'leads.view',
            'quotations.view',
            'quotations.create',
            'shipments.view',
            'shipments.create',
            'accounts.view',
            'job_orders.view',
            'job_orders.create',
            'templates.view',
            'templates.create',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        foreach ($roles as $r) {
            $role = Role::findOrCreate($r, $guard);

            switch ($role->name) {
                case 'Client':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view',
                        'quotations.view',
                        'quotations.create',
                        'shipments.view'
                    ])->get());
                    break;
                case 'Lead Account Specialist':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view',
                        'leads.view',
                        'quotations.view',
                        'quotations.create',
                        'shipments.view',
                        'accounts.view',
                        'job_orders.view',
                        'job_orders.create',
                        'templates.view',
                        'templates.create'
                    ])->get());
                    break;
                case 'Account Specialist':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view',
                        'leads.view',
                        'quotations.view',
                        'quotations.create',
                        'shipments.view',
                        'accounts.view',
                        'job_orders.view',
                        'job_orders.create',
                        'templates.view',
                        'templates.create'
                    ])->get());
                    break;
                case 'Marketing':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view'
                    ])->get());
                    break;
                case 'Human Resource':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view'
                    ])->get());
                    break;
                case 'Lead Operations':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view',
                        'job_orders.view',
                        'job_orders.create',
                        'shipments.view',
                        'shipments.create'
                    ])->get());
                    break;
                case 'Operations':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view',
                        'job_orders.view',
                        'job_orders.create',
                        'shipments.view',
                        'shipments.create'
                    ])->get());
                    break;
                case 'Lead Finance':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view',
                    ])->get());
                    break;
                case 'Finance':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view'
                    ])->get());
                    break;
                case 'IT':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view'
                    ])->get());
                    break;
                case 'Client Success':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view',
                        'job_orders.view',
                        'job_orders.create',
                        'shipments.view',
                        'shipments.create'
                    ])->get());
                    break;
                case 'Lead Client Success':
                    $role->syncPermissions(Permission::query()->where('guard_name', $guard)->whereIn('name', [
                        'dashboard.view',
                        'job_orders.view',
                        'job_orders.create',
                        'shipments.view',
                        'shipments.create'
                    ])->get());
                    break;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
