<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /** Hak akses per peran, sesuai matriks rute di DESIGN.md §5. */
    public const MATRIX = [
        'master.view' => ['admin', 'gudang', 'pimpinan'],
        'master.manage' => ['admin'],
        'users.manage' => ['admin'],
        'transactions.view' => ['admin', 'gudang', 'pimpinan'],
        'transactions.create' => ['admin', 'gudang'],
        'transactions.cancel' => ['admin', 'gudang'],
        'adjustments.create' => ['admin', 'gudang'],
        'adjustments.approve' => ['admin', 'pimpinan'],
        'opname.create' => ['admin', 'gudang'],
        'opname.approve' => ['admin', 'pimpinan'],
        'reports.view' => ['admin', 'gudang', 'pimpinan'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = collect(['admin', 'gudang', 'pimpinan'])
            ->mapWithKeys(fn ($name) => [$name => Role::findOrCreate($name, 'web')]);

        foreach (self::MATRIX as $permission => $roleNames) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ($roles as $name => $role) {
            $role->syncPermissions(
                collect(self::MATRIX)->filter(fn ($roleNames) => in_array($name, $roleNames))->keys()
            );
        }
    }
}
