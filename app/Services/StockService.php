<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\StockCondition;
use App\Exceptions\InsufficientStockException;
use App\Models\CylinderType;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya pintu untuk mengubah saldo stok.
 *
 * Setiap perubahan tabel `stocks` selalu disertai satu baris `stock_movements`
 * di dalam DB::transaction dengan lockForUpdate(), sehingga saldo dan buku besar
 * tidak pernah berbeda dan stok tidak bisa minus.
 */
class StockService
{
    /**
     * Ubah stok satu jenis tabung + kondisi sebesar $qty (positif = masuk, negatif = keluar).
     *
     * @throws InsufficientStockException
     */
    public function move(
        int $cylinderTypeId,
        StockCondition $condition,
        int $qty,
        Model $reference,
        MovementType $type,
        ?string $note = null,
    ): ?StockMovement {
        if ($qty === 0) {
            return null;
        }

        return DB::transaction(function () use ($cylinderTypeId, $condition, $qty, $reference, $type, $note) {
            $stock = $this->lockedStock($cylinderTypeId, $condition);

            if ($stock->quantity + $qty < 0) {
                throw new InsufficientStockException(
                    CylinderType::findOrFail($cylinderTypeId),
                    $condition,
                    $stock->quantity,
                    abs($qty),
                );
            }

            $stock->quantity += $qty;
            $stock->save();

            return StockMovement::create([
                'cylinder_type_id' => $cylinderTypeId,
                'condition' => $condition,
                'type' => $type,
                'qty' => $qty,
                'balance_after' => $stock->quantity,
                'reference_type' => $reference->getMorphClass(),
                'reference_id' => $reference->getKey(),
                'user_id' => auth()->id(),
                'note' => $note,
                'moved_at' => now(),
            ]);
        });
    }

    /**
     * Balik semua mutasi milik sebuah dokumen (dipakai saat transaksi dibatalkan).
     *
     * @throws InsufficientStockException bila stok sudah terpakai sehingga tidak bisa dibalik
     */
    public function reverse(Model $reference, ?string $note = null): void
    {
        DB::transaction(function () use ($reference, $note) {
            $movements = StockMovement::query()
                ->where('reference_type', $reference->getMorphClass())
                ->where('reference_id', $reference->getKey())
                ->where('type', '!=', MovementType::Pembatalan->value)
                ->orderBy('id')
                ->get();

            // Kurangi stok lebih dulu baru tambah, agar pengecekan minus akurat.
            foreach ($movements->sortBy(fn ($m) => -$m->qty) as $movement) {
                $this->move(
                    $movement->cylinder_type_id,
                    $movement->condition,
                    -$movement->qty,
                    $reference,
                    MovementType::Pembatalan,
                    $note,
                );
            }
        });
    }

    /** Saldo terkini semua jenis tabung: [cylinder_type_id => ['isi' => n, 'kosong' => n, 'rusak' => n]]. */
    public function balances(): array
    {
        $result = [];

        foreach (Stock::all() as $stock) {
            $result[$stock->cylinder_type_id][$stock->condition->value] = $stock->quantity;
        }

        return $result;
    }

    /** Pastikan setiap jenis tabung punya 3 baris saldo (isi, kosong, rusak). */
    public function ensureStockRows(CylinderType $cylinderType): void
    {
        foreach (StockCondition::cases() as $condition) {
            Stock::firstOrCreate(
                ['cylinder_type_id' => $cylinderType->id, 'condition' => $condition],
                ['quantity' => 0],
            );
        }
    }

    /**
     * Cek awal (tanpa kunci) agar petugas langsung tahu bila jumlah melebihi stok.
     * Pengecekan final tetap terjadi di move() saat stok benar-benar diubah.
     *
     * @throws InsufficientStockException
     */
    public function assertAvailable(int $cylinderTypeId, StockCondition $condition, int $qty): void
    {
        $available = (int) Stock::where('cylinder_type_id', $cylinderTypeId)
            ->where('condition', $condition)
            ->value('quantity');

        if ($qty > $available) {
            throw new InsufficientStockException(CylinderType::findOrFail($cylinderTypeId), $condition, $available, $qty);
        }
    }

    private function lockedStock(int $cylinderTypeId, StockCondition $condition): Stock
    {
        $locked = fn () => Stock::query()
            ->where('cylinder_type_id', $cylinderTypeId)
            ->where('condition', $condition)
            ->lockForUpdate()
            ->first();

        $stock = $locked();

        if (! $stock) {
            Stock::createOrFirst(
                ['cylinder_type_id' => $cylinderTypeId, 'condition' => $condition],
                ['quantity' => 0],
            );
            $stock = $locked();
        }

        return $stock;
    }
}
