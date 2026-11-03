<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data wajib: peran & hak akses, akun awal, jenis tabung (saldo 0).
     * Data contoh untuk demo: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            CylinderTypeSeeder::class,
        ]);
    }
}
