<?php

namespace Tests\Feature;

use App\Models\Adjustment;
use App\Models\StockOpname;
use Tests\TestCase;

/** Notifikasi lonceng: pengajuan, hasil persetujuan, dan stok di bawah minimum. */
class NotificationTest extends TestCase
{
    private function submitAdjustment(): Adjustment
    {
        $this->actingAs($this->user('gudang'))->post('/adjustments', [
            'date' => now()->toDateString(), 'type' => 'rusak', 'reason' => 'Valve bocor',
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'condition' => 'isi', 'qty' => 2]],
        ])->assertSessionHasNoErrors();

        return Adjustment::where('type', 'rusak')->firstOrFail();
    }

    public function test_pengajuan_penyesuaian_memberi_notifikasi_ke_penyetuju_saja(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $this->user('admin')->notifications()->delete();

        $adjustment = $this->submitAdjustment();

        $this->assertSame(1, $this->user('pimpinan')->unreadNotifications()->count());
        $this->assertSame(1, $this->user('admin')->unreadNotifications()->count());
        $this->assertSame(0, $this->user('gudang')->notifications()->count());
        $this->assertSame('Penyesuaian menunggu persetujuan', $this->user('pimpinan')->notifications()->first()->data['title']);
        $this->assertSame(route('adjustments.show', $adjustment), $this->user('pimpinan')->notifications()->first()->data['url']);
    }

    public function test_hasil_persetujuan_dikirim_ke_pengaju(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $adjustment = $this->submitAdjustment();

        $this->actingAs($this->user('pimpinan'))->post("/adjustments/{$adjustment->id}/reject", ['reason' => 'Foto bukti belum ada']);

        $notification = $this->user('gudang')->notifications()->first();
        $this->assertSame('Penyesuaian ditolak', $notification->data['title']);
        $this->assertStringContainsString('Foto bukti belum ada', $notification->data['message']);

        // Tugas "menunggu persetujuan" di lonceng penyetuju otomatis selesai.
        $this->assertSame(0, $this->user('pimpinan')->unreadNotifications()->count());
        $this->assertSame(0, $this->user('admin')->unreadNotifications()->count());
    }

    public function test_pengajuan_opname_memberi_notifikasi_ke_pimpinan(): void
    {
        $this->actingAs($this->user('gudang'))->post('/stock-opnames', ['date' => now()->toDateString()]);
        $opname = StockOpname::firstOrFail();
        $physical = $opname->items->mapWithKeys(fn ($i) => [$i->id => 0])->all();

        $this->put("/stock-opnames/{$opname->id}", ['physical' => $physical, 'action' => 'submit'])->assertSessionHasNoErrors();

        $this->assertSame('Stok opname menunggu persetujuan', $this->user('pimpinan')->notifications()->first()->data['title']);

        $this->actingAs($this->user('pimpinan'))->post("/stock-opnames/{$opname->id}/approve");
        $this->assertSame('Stok opname disetujui', $this->user('gudang')->notifications()->first()->data['title']);
    }

    public function test_peringatan_stok_minimum_hanya_saat_melewati_batas(): void
    {
        // Minimum LPG 3 kg = 300.
        $this->openingStock(['LPG3' => ['isi' => 400]]);
        $this->user('pimpinan')->notifications()->delete();

        $distribute = fn (int $qty) => $this->actingAs($this->user('gudang'))->post('/distributions', [
            'date' => now()->toDateString(), 'outlet_id' => $this->outlet()->id,
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'qty_full_out' => $qty, 'qty_empty_in' => $qty]],
        ])->assertSessionHasNoErrors();

        $distribute(50);  // 350, masih di atas minimum
        $this->assertSame(0, $this->user('pimpinan')->notifications()->count());

        $distribute(100); // 250, melewati batas → notifikasi
        $distribute(50);  // 200, sudah di bawah → tidak berulang

        $notifications = $this->user('pimpinan')->notifications()->get();
        $this->assertCount(1, $notifications);
        $this->assertSame('Stok di bawah minimum', $notifications->first()->data['title']);
        $this->assertSame(1, $this->user('gudang')->notifications()->count());
    }

    public function test_membuka_notifikasi_menandai_dibaca_dan_mengarahkan(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $adjustment = $this->submitAdjustment();
        $notification = $this->user('pimpinan')->notifications()->first();

        $this->actingAs($this->user('pimpinan'))
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('adjustments.show', $adjustment));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_tidak_bisa_membuka_notifikasi_milik_orang_lain(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $this->submitAdjustment();
        $notification = $this->user('pimpinan')->notifications()->first();

        $this->actingAs($this->user('gudang'))->get(route('notifications.open', $notification->id))->assertNotFound();
    }

    public function test_feed_json_dan_tandai_semua_dibaca(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 500]]);
        $this->submitAdjustment();
        $this->actingAs($this->user('pimpinan'));

        $this->getJson('/notifications')->assertOk()->assertJsonPath('unread', 1)->assertJsonCount(1, 'items');
        $this->postJson('/notifications/read-all')->assertOk()->assertJsonPath('unread', 0);
        $this->get('/notifications')->assertOk()->assertSee('Penyesuaian menunggu persetujuan');
    }
}
