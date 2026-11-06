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
            'inventory.view' => 'Ver inventario y movimientos',
            'inventory.manage' => 'Administrar productos y movimientos',
            'customers.view' => 'Ver clientes',
            'customers.manage' => 'Administrar clientes',
            'sales.view' => 'Ver ventas',
            'sales.create' => 'Registrar ventas',
            'sales.cancel' => 'Anular ventas',
            'catalogs.manage' => 'Administrar catálogos',
            'reports.view' => 'Ver reportes',
            'billing.manage' => 'Administrar plan y suscripción',
            'users.manage' => 'Administrar usuarios',
            'orders.view' => 'Ver pedidos públicos',
            'orders.manage' => 'Aprobar y rechazar pedidos públicos',
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
                'inventory.view', 'inventory.manage', 'customers.view', 'customers.manage',
                'sales.view', 'sales.create', 'sales.cancel',
                'billing.manage', 'users.manage', 'orders.view', 'orders.manage',
            ],
            'veterinario' => [
                'animals.view', 'animals.update', 'events.create', 'events.delete', 'reports.view',
                'inventory.view', 'customers.view', 'sales.view', 'orders.view',
            ],
            'operario' => [
                'animals.view', 'animals.create', 'animals.update', 'events.create',
                'inventory.view', 'inventory.manage', 'customers.view', 'sales.view', 'sales.create',
            ],
        ];

        foreach ($roles as $role => $rolePermissions) {
            $model = Role::findOrCreate($role, 'web');
            $model->syncPermissions($rolePermissions);
        }
    }
}
