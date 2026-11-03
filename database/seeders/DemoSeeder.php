<?php

namespace Database\Seeders;

use App\Exceptions\InsufficientStockException;
use App\Models\CylinderType;
use App\Models\Outlet;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AdjustmentService;
use App\Services\DistributionService;
use App\Services\ReceiptService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Data contoh untuk demo: SPBE, pangkalan + kuota, kendaraan, dan transaksi 30 hari terakhir.
 * Jalankan: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = collect([
            ['code' => 'SPBE01', 'name' => 'SPBE PT Palembang Gas Mandiri', 'address' => 'Jl. Mayor Zen, Kalidoni, Palembang', 'phone' => '0711-712345'],
            ['code' => 'SPBE02', 'name' => 'SPBE PT Sumber Energi Musi', 'address' => 'Jl. Soekarno-Hatta, Alang-Alang Lebar, Palembang', 'phone' => '0711-418899'],
        ])->map(fn ($s) => Supplier::firstOrCreate(['code' => $s['code']], $s));

        $vehicles = collect([
            ['plate_number' => 'BG 8123 KA', 'type' => 'Truk Engkel', 'driver_name' => 'Rahmat'],
            ['plate_number' => 'BG 8456 KB', 'type' => 'Truk Engkel', 'driver_name' => 'Joni Saputra'],
            ['plate_number' => 'BG 9012 KC', 'type' => 'Pick Up', 'driver_name' => 'Hendra'],
        ])->map(fn ($v) => Vehicle::firstOrCreate(['plate_number' => $v['plate_number']], $v));

        $types = CylinderType::orderBy('weight_kg')->get()->keyBy('code');

        $outletNames = [
            ['Pangkalan Sumber Rezeki', 'H. Usman', 'Jl. Kol. H. Burlian Km 7'],
            ['Pangkalan Berkah Jaya', 'Siti Aminah', 'Jl. Sukabangun II'],
            ['Pangkalan Maju Bersama', 'Darmawan', 'Jl. Demang Lebar Daun'],
            ['Pangkalan Karya Mandiri', 'Yuliana', 'Jl. Sematang Borang'],
            ['Pangkalan Sinar Abadi', 'Edi Susanto', 'Jl. Kapten A. Rivai'],
            ['Pangkalan Mitra Usaha', 'Rosmala', 'Jl. Bypass Alang-Alang Lebar'],
        ];

        $outlets = collect($outletNames)->map(function ($row, $i) use ($types) {
            $outlet = Outlet::firstOrCreate(
                ['code' => sprintf('PKL%03d', $i + 1)],
                ['name' => $row[0], 'owner_name' => $row[1], 'address' => $row[2].', Palembang', 'phone' => '0812'.random_int(10000000, 99999999)],
            );
            $outlet->quotas()->updateOrCreate(['cylinder_type_id' => $types['LPG3']->id], ['monthly_quota' => [1000, 800, 600, 600, 500, 400][$i]]);
            if ($i < 3) {
                $outlet->quotas()->updateOrCreate(['cylinder_type_id' => $types['LPG12']->id], ['monthly_quota' => 100]);
                $outlet->quotas()->updateOrCreate(['cylinder_type_id' => $types['BG55']->id], ['monthly_quota' => 60]);
            }

            return $outlet;
        });

        // Transaksi dicatat atas nama petugas gudang; persetujuan oleh pimpinan.
        $gudang = User::where('username', 'gudang')->firstOrFail();
        $pimpinan = User::where('username', 'pimpinan')->firstOrFail();

        $receipts = app(ReceiptService::class);
        $distributions = app(DistributionService::class);
        $adjustments = app(AdjustmentService::class);

        $start = now()->subDays(30)->startOfDay();

        // Saldo awal lewat penyesuaian koreksi yang disetujui.
        $this->at($start->copy()->setTime(7, 30), $gudang, function () use ($adjustments, $types, $pimpinan) {
            $opening = $adjustments->create(
                ['date' => now()->toDateString(), 'type' => 'koreksi', 'reason' => 'Saldo awal saat sistem mulai digunakan'],
                [
                    ['cylinder_type_id' => $types['LPG3']->id, 'condition' => 'isi', 'direction' => 'plus', 'qty' => 1200],
                    ['cylinder_type_id' => $types['LPG3']->id, 'condition' => 'kosong', 'direction' => 'plus', 'qty' => 800],
                    ['cylinder_type_id' => $types['LPG3']->id, 'condition' => 'rusak', 'direction' => 'plus', 'qty' => 8],
                    ['cylinder_type_id' => $types['BG55']->id, 'condition' => 'isi', 'direction' => 'plus', 'qty' => 90],
                    ['cylinder_type_id' => $types['BG55']->id, 'condition' => 'kosong', 'direction' => 'plus', 'qty' => 40],
                    ['cylinder_type_id' => $types['LPG12']->id, 'condition' => 'isi', 'direction' => 'plus', 'qty' => 150],
                    ['cylinder_type_id' => $types['LPG12']->id, 'condition' => 'kosong', 'direction' => 'plus', 'qty' => 80],
                    ['cylinder_type_id' => $types['LPG50']->id, 'condition' => 'isi', 'direction' => 'plus', 'qty' => 20],
                    ['cylinder_type_id' => $types['LPG50']->id, 'condition' => 'kosong', 'direction' => 'plus', 'qty' => 10],
                ],
            );
            Auth::setUser($pimpinan);
            $adjustments->approve($opening, 'Saldo awal sesuai hitung fisik');
        });

        for ($day = 0; $day < 30; $day++) {
            $date = $start->copy()->addDays($day);

            if ($date->isSunday()) {
                continue;
            }

            // Truk dari SPBE datang tiap 2 hari.
            if ($day % 2 === 0) {
                $empty = app(StockService::class)->balances();
                $item = fn ($code, $full) => [
                    'cylinder_type_id' => $types[$code]->id,
                    'qty_full_in' => $full,
                    'qty_empty_out' => min($full, $empty[$types[$code]->id]['kosong'] ?? 0),
                ];

                $this->at($date->copy()->setTime(8, random_int(0, 59)), $gudang, fn () => $receipts->create(
                    [
                        'date' => $date->toDateString(),
                        'ref_document' => 'LO-'.$date->format('ymd').'-'.random_int(100, 999),
                        'supplier_id' => $suppliers->random()->id,
                        'vehicle_id' => $vehicles[0]->id,
                    ],
                    [$item('LPG3', 480), $item('LPG12', 15), $item('BG55', 6)],
                ));
            }

            // 3–4 pengiriman ke pangkalan per hari.
            foreach ($outlets->random(random_int(3, 4)) as $i => $outlet) {
                $full3 = random_int(4, 9) * 10;
                $items = [['cylinder_type_id' => $types['LPG3']->id, 'qty_full_out' => $full3, 'qty_empty_in' => $full3 - random_int(0, 1) * 5]];
                if ($outlet->quotas->count() > 1) {
                    $full12 = random_int(2, 6);
                    $full55 = random_int(0, 3);
                    $items[] = ['cylinder_type_id' => $types['LPG12']->id, 'qty_full_out' => $full12, 'qty_empty_in' => $full12];
                    $items[] = ['cylinder_type_id' => $types['BG55']->id, 'qty_full_out' => $full55, 'qty_empty_in' => $full55];
                }

                $this->at($date->copy()->setTime(10 + $i, random_int(0, 59)), $gudang, fn () => $distributions->create(
                    ['date' => $date->toDateString(), 'outlet_id' => $outlet->id, 'vehicle_id' => $vehicles->random()->id],
                    $items,
                ));
            }
        }

        // Satu penyesuaian rusak menunggu persetujuan, untuk contoh di dashboard.
        $this->at(now()->subHours(2), $gudang, fn () => $adjustments->create(
            ['date' => now()->toDateString(), 'type' => 'rusak', 'reason' => 'Tabung bocor di bagian valve saat bongkar muat'],
            [['cylinder_type_id' => $types['LPG3']->id, 'condition' => 'isi', 'qty' => 3]],
        ));
    }

    /** Jalankan aksi seolah-olah terjadi pada waktu & oleh pengguna tertentu. */
    private function at(Carbon $time, User $user, callable $action): void
    {
        Carbon::setTestNow($time);
        Auth::setUser($user);

        try {
            $action();
        } catch (InsufficientStockException) {
            // Lewati transaksi contoh yang kebetulan melebihi stok.
        } finally {
            Carbon::setTestNow();
        }
    }
}
