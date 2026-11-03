<?php

namespace Tests;

use App\Models\CylinderType;
use App\Models\Outlet;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Jalankan DatabaseSeeder: peran, 3 akun (admin/gudang/pimpinan), 4 jenis tabung. */
    protected bool $seed = true;

    protected function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    protected function type(string $code): CylinderType
    {
        return CylinderType::where('code', $code)->firstOrFail();
    }

    protected function stock(string $code, string $condition): int
    {
        return (int) Stock::where('cylinder_type_id', $this->type($code)->id)->where('condition', $condition)->value('quantity');
    }

    /** Isi saldo awal lewat penyesuaian koreksi yang langsung disetujui. */
    protected function openingStock(array $balances): void
    {
        $this->actingAs($this->user('admin'));
        $service = app(AdjustmentService::class);

        $rows = [];
        foreach ($balances as $code => $conditions) {
            foreach ($conditions as $condition => $qty) {
                $rows[] = ['cylinder_type_id' => $this->type($code)->id, 'condition' => $condition, 'direction' => 'plus', 'qty' => $qty];
            }
        }

        $service->approve($service->create(['date' => now()->toDateString(), 'type' => 'koreksi', 'reason' => 'Saldo awal pengujian'], $rows)->load('items'));
    }

    protected function outlet(): Outlet
    {
        return Outlet::firstOrCreate(['code' => 'PKL001'], ['name' => 'Pangkalan Sumber Rezeki', 'owner_name' => 'H. Usman']);
    }

    protected function supplier(): Supplier
    {
        return Supplier::firstOrCreate(['code' => 'SPBE01'], ['name' => 'SPBE Uji']);
    }
}
