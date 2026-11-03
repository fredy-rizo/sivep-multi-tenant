<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'animals.view' => 'Ver animales',
            'animals.create' => 'Crear animales',
            'animals.update' => 'Editar animales',
            'animals.delete' => 'Eliminar animales',
            'events.create' => 'Registrar eventos',
            'events.delete' => 'Eliminar eventos',
            'catalogs.manage' => 'Administrar catálogos',
            'reports.view' => 'Ver reportes',
            'billing.manage' => 'Administrar plan y suscripción',
            'users.manage' => 'Administrar usuarios',
            'organizations.manage' => 'Administrar organizaciones',
        ];

        foreach ($permissions as $name => $label) {
            Permission::findOrCreate($name, 'web');
        }

        $roles = [
            'super_admin' => array_keys($permissions),
            'admin_granja' => [
                'animals.view', 'animals.create', 'animals.update', 'animals.delete',
                'events.create', 'events.delete', 'catalogs.manage', 'reports.view',
                'billing.manage', 'users.manage',
            ],
            'veterinario' => [
                'animals.view', 'animals.update', 'events.create', 'events.delete', 'reports.view',
            ],
            'operario' => [
                'animals.view', 'animals.create', 'animals.update', 'events.create',
            ],
        ];

        foreach ($roles as $role => $rolePermissions) {
            $model = Role::findOrCreate($role, 'web');
            $model->syncPermissions($rolePermissions);
        }
    }
}
