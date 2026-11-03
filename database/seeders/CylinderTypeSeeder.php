<?php

namespace Database\Seeders;

use App\Models\CylinderType;
use Illuminate\Database\Seeder;

/** Jenis tabung awal (PRD §6). Saldo stok 0 dibuat otomatis lewat event model. */
class CylinderTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'LPG3', 'name' => 'LPG 3 kg', 'weight_kg' => 3, 'min_stock' => 300],
            ['code' => 'BG55', 'name' => 'Bright Gas 5,5 kg', 'weight_kg' => 5.5, 'min_stock' => 30],
            ['code' => 'LPG12', 'name' => 'LPG 12 kg', 'weight_kg' => 12, 'min_stock' => 50],
            ['code' => 'LPG50', 'name' => 'LPG 50 kg', 'weight_kg' => 50, 'min_stock' => 10],
        ];

        foreach ($types as $type) {
            CylinderType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
