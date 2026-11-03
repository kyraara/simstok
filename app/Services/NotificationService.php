<?php

namespace App\Services;

use App\Models\Adjustment;
use App\Models\CylinderType;
use App\Models\StockOpname;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Mengirim notifikasi lonceng ke pengguna yang relevan.
 * Dikirim setelah transaksi database berhasil (afterCommit), jadi tidak ada
 * notifikasi untuk transaksi yang gagal/di-rollback.
 */
class NotificationService
{
    public function adjustmentSubmitted(Adjustment $adjustment): void
    {
        $this->toPermission('adjustments.approve', new SystemNotification(
            'Penyesuaian menunggu persetujuan',
            "{$adjustment->number} · {$adjustment->type->label()} oleh {$adjustment->user->name}",
            route('adjustments.show', $adjustment),
            'adjustment',
            'amber',
        ));
    }

    public function adjustmentReviewed(Adjustment $adjustment, bool $approved): void
    {
        $this->resolveTask(route('adjustments.show', $adjustment));

        $this->toUser($adjustment->user, new SystemNotification(
            $approved ? 'Penyesuaian disetujui' : 'Penyesuaian ditolak',
            $adjustment->number.($approved ? ' — stok sudah diperbarui.' : ' — '.$adjustment->review_note),
            route('adjustments.show', $adjustment),
            'adjustment',
            $approved ? 'gas' : 'flame',
        ));
    }

    public function opnameSubmitted(StockOpname $opname): void
    {
        $this->toPermission('opname.approve', new SystemNotification(
            'Stok opname menunggu persetujuan',
            "{$opname->number} oleh {$opname->user->name}",
            route('stock-opnames.show', $opname),
            'opname',
            'amber',
        ));
    }

    public function opnameReviewed(StockOpname $opname, bool $approved): void
    {
        $this->resolveTask(route('stock-opnames.show', $opname));

        $this->toUser($opname->user, new SystemNotification(
            $approved ? 'Stok opname disetujui' : 'Stok opname ditolak',
            $opname->number.($approved ? ' — selisih sudah dibukukan.' : ' — '.$opname->review_note),
            route('stock-opnames.show', $opname),
            'opname',
            $approved ? 'gas' : 'flame',
        ));
    }

    /** Stok isi baru saja turun melewati batas minimum. */
    public function lowStock(CylinderType $type, int $balance): void
    {
        $this->toPermission('transactions.view', new SystemNotification(
            'Stok di bawah minimum',
            sprintf('%s tinggal %s tabung isi (minimum %s).', $type->name, number_format($balance, 0, ',', '.'), number_format($type->min_stock, 0, ',', '.')),
            route('stock-card', ['cylinder_type_id' => $type->id]),
            'alert',
            'flame',
        ), exceptActor: false);
    }

    /**
     * Dokumen sudah diproses (disetujui/ditolak/dibatalkan): notifikasi "menunggu persetujuan"
     * miliknya di lonceng semua penyetuju ditandai dibaca agar tidak menumpuk.
     */
    public function resolveTask(string $url): void
    {
        DB::afterCommit(fn () => DatabaseNotification::query()
            ->whereNull('read_at')
            ->where('data->url', $url)
            ->where('data->color', 'amber')
            ->update(['read_at' => now()]));
    }

    /** Kirim ke semua pengguna aktif yang punya permission tertentu, kecuali pelaku aksi. */
    private function toPermission(string $permission, SystemNotification $notification, bool $exceptActor = true): void
    {
        $this->send(fn () => User::permission($permission)
            ->where('is_active', true)
            ->when($exceptActor && auth()->id(), fn ($q) => $q->whereKeyNot(auth()->id()))
            ->get(), $notification);
    }

    /** Kirim ke satu pengguna, kecuali dia sendiri pelaku aksinya. */
    private function toUser(?User $user, SystemNotification $notification): void
    {
        if (! $user || $user->id === auth()->id()) {
            return;
        }

        $this->send(fn () => collect([$user]), $notification);
    }

    /** @param  callable(): Collection<int, User>  $recipients */
    private function send(callable $recipients, SystemNotification $notification): void
    {
        DB::afterCommit(function () use ($recipients, $notification) {
            $users = $recipients();
            if ($users->isNotEmpty()) {
                Notification::send($users, $notification);
            }
        });
    }
}
