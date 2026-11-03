<?php

namespace Tests\Feature;

use App\Models\Adjustment;
use App\Models\Distribution;
use App\Models\Outlet;
use App\Models\Receipt;
use App\Models\StockOpname;
use App\Models\Vehicle;
use App\Services\DistributionService;
use App\Services\ReceiptService;
use App\Services\StockOpnameService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Smoke test: semua halaman, PDF, dan Excel tampil tanpa error (skenario 9). */
class PagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->openingStock(['LPG3' => ['isi' => 1000, 'kosong' => 500], 'LPG12' => ['isi' => 50]]);
        $outlet = $this->outlet();
        $outlet->quotas()->create(['cylinder_type_id' => $this->type('LPG3')->id, 'monthly_quota' => 1000]);
        $vehicle = Vehicle::create(['plate_number' => 'BG 1234 XX', 'driver_name' => 'Rahmat']);

        $this->actingAs($this->user('gudang'));
        app(ReceiptService::class)->create(
            ['date' => now()->toDateString(), 'supplier_id' => $this->supplier()->id, 'vehicle_id' => $vehicle->id],
            [['cylinder_type_id' => $this->type('LPG3')->id, 'qty_full_in' => 560, 'qty_empty_out' => 500]],
        );
        app(DistributionService::class)->create(
            ['date' => now()->toDateString(), 'outlet_id' => $outlet->id, 'vehicle_id' => $vehicle->id],
            [['cylinder_type_id' => $this->type('LPG3')->id, 'qty_full_out' => 200, 'qty_empty_in' => 190]],
        );
        $opname = app(StockOpnameService::class)->create(now()->toDateString());
        app(StockOpnameService::class)->saveCount($opname->load('items'), $opname->items->pluck('system_qty', 'id')->all(), null, true);
    }

    public static function adminPages(): array
    {
        return array_map(fn ($u) => [$u], [
            '/dashboard', '/profile',
            '/receipts', '/receipts/create', '/receipts/{receipt}',
            '/distributions', '/distributions/create', '/distributions/{distribution}', '/distributions/quota?outlet_id={outlet}',
            '/adjustments', '/adjustments/create', '/adjustments/{adjustment}',
            '/stock-opnames', '/stock-opnames/{opname}',
            '/stock-card', '/stock-card?condition=kosong&from=2020-01-01',
            '/reports', '/reports/receipts', '/reports/distributions', '/reports/stock-position',
            '/master/cylinder-types', '/master/cylinder-types/create',
            '/master/suppliers', '/master/suppliers/create',
            '/master/outlets', '/master/outlets/create', '/master/outlets/{outlet}', '/master/outlets/{outlet}/edit',
            '/master/vehicles', '/master/vehicles/create',
            '/users', '/users/create',
        ]);
    }

    #[DataProvider('adminPages')]
    public function test_halaman_tampil_untuk_admin(string $url): void
    {
        $this->actingAs($this->user('admin'))->get($this->resolve($url))->assertOk();
    }

    public static function exports(): array
    {
        return [
            ['/distributions/{distribution}/print', 'application/pdf'],
            ['/stock-opnames/{opname}/pdf', 'application/pdf'],
            ['/stock-card?export=pdf', 'application/pdf'],
            ['/stock-card?export=xlsx', 'spreadsheetml'],
            ['/reports/receipts?export=pdf', 'application/pdf'],
            ['/reports/receipts?export=xlsx', 'spreadsheetml'],
            ['/reports/distributions?export=pdf', 'application/pdf'],
            ['/reports/distributions?export=xlsx', 'spreadsheetml'],
            ['/reports/stock-position?export=pdf', 'application/pdf'],
            ['/reports/stock-position?export=xlsx', 'spreadsheetml'],
        ];
    }

    #[DataProvider('exports')]
    public function test_pdf_dan_excel_terunduh(string $url, string $type): void
    {
        $response = $this->actingAs($this->user('pimpinan'))->get($this->resolve($url))->assertOk();

        $this->assertStringContainsString($type, $response->headers->get('content-type'));
    }

    public function test_dashboard_menampilkan_stok_dan_peringatan_minimum(): void
    {
        // LPG 12 kg isi 50, minimum 50 → tidak di bawah; Bright Gas 0 < 30 → di bawah minimum.
        $this->actingAs($this->user('pimpinan'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Stok isi di bawah minimum')
            ->assertSee('Bright Gas 5,5 kg')
            ->assertSee('1.360');
    }

    public function test_api_kuota_mengembalikan_realisasi_bulan_berjalan(): void
    {
        $this->actingAs($this->user('gudang'))
            ->getJson('/distributions/quota?outlet_id='.$this->outlet()->id)
            ->assertOk()
            ->assertJsonPath($this->type('LPG3')->id.'.quota', 1000)
            ->assertJsonPath($this->type('LPG3')->id.'.used', 200);
    }

    private function resolve(string $url): string
    {
        return strtr($url, [
            '{receipt}' => Receipt::first()->id,
            '{distribution}' => Distribution::first()->id,
            '{adjustment}' => Adjustment::first()->id,
            '{opname}' => StockOpname::first()->id,
            '{outlet}' => Outlet::first()->id,
        ]);
    }
}
