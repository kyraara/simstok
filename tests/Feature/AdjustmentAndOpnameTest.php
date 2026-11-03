<?php

namespace Tests\Feature;

use App\Enums\AdjustmentType;
use App\Enums\ApprovalStatus;
use App\Enums\OpnameStatus;
use App\Models\Adjustment;
use App\Models\StockMovement;
use App\Models\StockOpname;
use Tests\TestCase;

/** F-10 penyesuaian, F-14–F-16 stok opname, skenario black box 7. */
class AdjustmentAndOpnameTest extends TestCase
{
    public function test_penyesuaian_rusak_baru_mengubah_stok_setelah_disetujui(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 100]]);

        $this->actingAs($this->user('gudang'))->post('/adjustments', [
            'date' => now()->toDateString(),
            'type' => 'rusak',
            'reason' => 'Valve bocor saat bongkar',
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'condition' => 'isi', 'qty' => 3]],
        ])->assertSessionHasNoErrors();

        $adjustment = Adjustment::where('type', 'rusak')->firstOrFail();
        $this->assertSame(ApprovalStatus::Pending, $adjustment->status);
        $this->assertSame(100, $this->stock('LPG3', 'isi'));

        $this->actingAs($this->user('pimpinan'))->post("/adjustments/{$adjustment->id}/approve")->assertSessionHasNoErrors();

        $this->assertSame(97, $this->stock('LPG3', 'isi'));
        $this->assertSame(3, $this->stock('LPG3', 'rusak'));
        $this->assertSame(ApprovalStatus::Approved, $adjustment->fresh()->status);
    }

    public function test_penyesuaian_wajib_alasan(): void
    {
        $this->actingAs($this->user('gudang'))->post('/adjustments', [
            'date' => now()->toDateString(), 'type' => 'hilang', 'reason' => '',
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'condition' => 'isi', 'qty' => 1]],
        ])->assertSessionHasErrors('reason');
    }

    public function test_penyesuaian_melebihi_stok_ditolak(): void
    {
        $this->openingStock(['LPG3' => ['kosong' => 5]]);

        $this->actingAs($this->user('gudang'))->post('/adjustments', [
            'date' => now()->toDateString(), 'type' => 'hilang', 'reason' => 'Hilang di pangkalan',
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'condition' => 'kosong', 'qty' => 10]],
        ])->assertSessionHasErrors('stock');

        $this->assertSame(1, Adjustment::count());
    }

    public function test_penyesuaian_ditolak_tidak_mengubah_stok(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 100]]);
        $this->actingAs($this->user('gudang'))->post('/adjustments', [
            'date' => now()->toDateString(), 'type' => 'hilang', 'reason' => 'Dugaan hilang',
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'condition' => 'isi', 'qty' => 5]],
        ]);
        $adjustment = Adjustment::where('type', 'hilang')->firstOrFail();

        $this->actingAs($this->user('pimpinan'))->post("/adjustments/{$adjustment->id}/reject", ['reason' => 'Sudah ditemukan'])->assertSessionHasNoErrors();

        $this->assertSame(ApprovalStatus::Rejected, $adjustment->fresh()->status);
        $this->assertSame(100, $this->stock('LPG3', 'isi'));
    }

    public function test_opname_disetujui_membuat_stok_sama_dengan_fisik(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500, 'kosong' => 200, 'rusak' => 5]]);

        $this->actingAs($this->user('gudang'))->post('/stock-opnames', ['date' => now()->toDateString()])->assertSessionHasNoErrors();
        $opname = StockOpname::firstOrFail();
        $this->assertSame(OpnameStatus::Draft, $opname->status);

        // Isi semua kondisi semua jenis: LPG3 berselisih, jenis lain sesuai sistem (0).
        $physical = [];
        foreach ($opname->items()->with('cylinderType')->get() as $item) {
            $physical[$item->id] = match ([$item->cylinderType->code, $item->condition->value]) {
                ['LPG3', 'isi'] => 495,
                ['LPG3', 'kosong'] => 203,
                ['LPG3', 'rusak'] => 5,
                default => 0,
            };
        }

        $this->put("/stock-opnames/{$opname->id}", ['physical' => $physical, 'action' => 'submit'])->assertSessionHasNoErrors();
        $this->assertSame(OpnameStatus::Submitted, $opname->fresh()->status);
        $this->assertSame(500, $this->stock('LPG3', 'isi'), 'Stok belum berubah sebelum disetujui');

        $this->actingAs($this->user('pimpinan'))->post("/stock-opnames/{$opname->id}/approve")->assertSessionHasNoErrors();

        $this->assertSame(OpnameStatus::Approved, $opname->fresh()->status);
        $this->assertSame(495, $this->stock('LPG3', 'isi'));
        $this->assertSame(203, $this->stock('LPG3', 'kosong'));
        $this->assertSame(5, $this->stock('LPG3', 'rusak'));

        $adjustment = Adjustment::where('type', AdjustmentType::Opname)->firstOrFail();
        $this->assertSame($opname->id, $adjustment->stock_opname_id);
        $this->assertSame(2, StockMovement::where('type', 'opname')->count());
    }

    public function test_opname_tidak_bisa_diajukan_bila_ada_jumlah_fisik_kosong(): void
    {
        $this->actingAs($this->user('gudang'))->post('/stock-opnames', ['date' => now()->toDateString()]);
        $opname = StockOpname::firstOrFail();

        $this->put("/stock-opnames/{$opname->id}", ['physical' => [], 'action' => 'submit'])->assertSessionHasErrors('general');
        $this->assertSame(OpnameStatus::Draft, $opname->fresh()->status);
    }

    public function test_hanya_satu_sesi_opname_terbuka(): void
    {
        $this->actingAs($this->user('gudang'))->post('/stock-opnames', ['date' => now()->toDateString()]);
        $this->post('/stock-opnames', ['date' => now()->toDateString()])->assertSessionHasErrors('general');

        $this->assertSame(1, StockOpname::count());
    }

    public function test_selisih_opname_tetap_benar_walau_ada_distribusi_sebelum_disetujui(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $this->actingAs($this->user('gudang'))->post('/stock-opnames', ['date' => now()->toDateString()]);
        $opname = StockOpname::firstOrFail();
        $physical = $opname->items->mapWithKeys(fn ($i) => [$i->id => $i->system_qty])->all();
        $lpg3Isi = $opname->items->first(fn ($i) => $i->cylinder_type_id === $this->type('LPG3')->id && $i->condition->value === 'isi');
        $physical[$lpg3Isi->id] = 490; // fisik kurang 10
        $this->put("/stock-opnames/{$opname->id}", ['physical' => $physical, 'action' => 'submit']);

        // Distribusi 100 terjadi setelah hitung fisik, sebelum persetujuan.
        $this->post('/distributions', [
            'date' => now()->toDateString(), 'outlet_id' => $this->outlet()->id,
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'qty_full_out' => 100, 'qty_empty_in' => 100]],
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->user('pimpinan'))->post("/stock-opnames/{$opname->id}/approve");

        // 490 fisik saat hitung − 100 distribusi = 390
        $this->assertSame(390, $this->stock('LPG3', 'isi'));
    }
}
