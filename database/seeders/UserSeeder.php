<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun awal untuk tiap peran. Password default: "password" — WAJIB diganti setelah instalasi.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrator', 'username' => 'admin', 'email' => 'admin@simstok.test', 'role' => 'admin'],
            ['name' => 'Petugas Gudang', 'username' => 'gudang', 'email' => 'gudang@simstok.test', 'role' => 'gudang'],
            ['name' => 'Pimpinan', 'username' => 'pimpinan', 'email' => 'pimpinan@simstok.test', 'role' => 'pimpinan'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => 'password',
                    'is_active' => true,
                ],
            );
            $user->syncRoles([$data['role']]);
        }
    }
}
