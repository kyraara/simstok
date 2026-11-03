<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Distribution;
use App\Models\Receipt;
use App\Models\StockMovement;
use Tests\TestCase;

/** Skenario black box 3–6 dan kriteria penerimaan F-09 (PRD §10). */
class StockTransactionTest extends TestCase
{
    private function distribute(int $full, int $empty)
    {
        return $this->actingAs($this->user('gudang'))->from('/distributions/create')->post('/distributions', [
            'date' => now()->toDateString(),
            'outlet_id' => $this->outlet()->id,
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'qty_full_out' => $full, 'qty_empty_in' => $empty]],
        ]);
    }

    public function test_distribusi_mengurangi_isi_dan_menambah_kosong(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500, 'kosong' => 100]]);

        $this->distribute(200, 190)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(300, $this->stock('LPG3', 'isi'));
        $this->assertSame(290, $this->stock('LPG3', 'kosong'));
    }

    public function test_nomor_surat_jalan_otomatis_berformat_sj_yyyymm_0001(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);

        $this->distribute(10, 10);
        $this->distribute(10, 10);

        $prefix = 'SJ/'.now()->format('Ym').'/';
        $this->assertSame([$prefix.'0001', $prefix.'0002'], Distribution::orderBy('id')->pluck('number')->all());
    }

    public function test_distribusi_melebihi_stok_ditolak_dan_tidak_tersimpan(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);

        $this->distribute(2000, 2000)
            ->assertRedirect('/distributions/create')
            ->assertSessionHasErrors('items.0.qty_full_out');

        $this->assertSame(0, Distribution::count());
        $this->assertSame(500, $this->stock('LPG3', 'isi'));
    }

    public function test_distribusi_tanpa_jumlah_ditolak(): void
    {
        $this->distribute(0, 0)->assertSessionHasErrors('items');
    }

    public function test_penerimaan_spbe_menambah_isi_dan_mengurangi_kosong(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 100, 'kosong' => 600]]);

        $this->actingAs($this->user('gudang'))->post('/receipts', [
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier()->id,
            'ref_document' => 'LO-001',
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'qty_full_in' => 560, 'qty_empty_out' => 560]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(660, $this->stock('LPG3', 'isi'));
        $this->assertSame(40, $this->stock('LPG3', 'kosong'));
        $this->assertStringStartsWith('PN/'.now()->format('Ym').'/', Receipt::first()->number);
    }

    public function test_penerimaan_kosong_dikirim_melebihi_stok_kosong_ditolak(): void
    {
        $this->actingAs($this->user('gudang'))->post('/receipts', [
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier()->id,
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'qty_full_in' => 560, 'qty_empty_out' => 560]],
        ])->assertSessionHasErrors('items.0.qty_empty_out');

        $this->assertSame(0, Receipt::count());
    }

    public function test_batal_distribusi_mengembalikan_stok_dan_tercatat_di_kartu_stok(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $this->distribute(200, 200);
        $distribution = Distribution::first();

        $this->post("/distributions/{$distribution->id}/cancel", ['reason' => 'Salah input pangkalan'])->assertSessionHasNoErrors();

        $distribution->refresh();
        $this->assertSame(TransactionStatus::Cancelled, $distribution->status);
        $this->assertSame($this->user('gudang')->id, $distribution->cancelled_by);
        $this->assertSame(500, $this->stock('LPG3', 'isi'));
        $this->assertSame(0, $this->stock('LPG3', 'kosong'));
        $this->assertSame(2, StockMovement::where('type', 'pembatalan')->count());
    }

    public function test_batal_tanpa_alasan_ditolak(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $this->distribute(200, 200);

        $this->post('/distributions/'.Distribution::first()->id.'/cancel', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame(300, $this->stock('LPG3', 'isi'));
    }

    public function test_batal_penerimaan_ditolak_bila_tabung_isi_sudah_terdistribusi(): void
    {
        $this->actingAs($this->user('gudang'))->post('/receipts', [
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier()->id,
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'qty_full_in' => 100, 'qty_empty_out' => 0]],
        ]);
        $this->distribute(80, 80);

        $this->post('/receipts/'.Receipt::first()->id.'/cancel', ['reason' => 'Dokumen salah'])->assertSessionHasErrors('stock');

        $this->assertTrue(Receipt::first()->isPosted());
        $this->assertSame(20, $this->stock('LPG3', 'isi'));
    }

    public function test_setiap_mutasi_mencatat_saldo_dan_pengguna(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $this->distribute(200, 200);

        $movement = StockMovement::where('type', 'distribusi')->where('condition', 'isi')->first();
        $this->assertSame(-200, $movement->qty);
        $this->assertSame(300, $movement->balance_after);
        $this->assertSame($this->user('gudang')->id, $movement->user_id);
        $this->assertSame('distribution', $movement->reference_type);
    }
}
