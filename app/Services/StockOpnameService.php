<?php

namespace App\Services;

use App\Enums\AdjustmentType;
use App\Enums\ApprovalStatus;
use App\Enums\OpnameStatus;
use App\Enums\StockCondition;
use App\Exceptions\BusinessRuleException;
use App\Models\Adjustment;
use App\Models\CylinderType;
use App\Models\StockOpname;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Alur stok opname: draft (hitung fisik) → submitted (stok sistem dipotret) → approved/rejected.
 *
 * Selisih = fisik − stok sistem saat hitung diajukan. Saat disetujui, selisih itu
 * dibukukan sebagai penyesuaian bertipe "opname". Memakai potret saat pengajuan
 * (bukan saat persetujuan) membuat hasilnya tetap benar walau ada transaksi
 * di antara pengajuan dan persetujuan.
 */
class StockOpnameService
{
    public function __construct(
        private StockService $stock,
        private DocumentNumberService $numbers,
        private AdjustmentService $adjustments,
        private NotificationService $notifications,
    ) {}

    public function openSession(): ?StockOpname
    {
        return StockOpname::whereIn('status', [OpnameStatus::Draft, OpnameStatus::Submitted])->first();
    }

    public function create(string $date, ?string $note = null): StockOpname
    {
        if ($open = $this->openSession()) {
            throw new BusinessRuleException("Masih ada sesi stok opname yang belum selesai ({$open->number}).");
        }

        return DB::transaction(function () use ($date, $note) {
            $opname = StockOpname::create([
                'number' => $this->numbers->next(StockOpname::PREFIX, Carbon::parse($date)),
                'date' => $date,
                'status' => OpnameStatus::Draft,
                'user_id' => auth()->id(),
                'note' => $note,
            ]);

            $balances = $this->stock->balances();

            foreach (CylinderType::active()->orderBy('weight_kg')->get() as $type) {
                foreach (StockCondition::cases() as $condition) {
                    $opname->items()->create([
                        'cylinder_type_id' => $type->id,
                        'condition' => $condition,
                        'system_qty' => $balances[$type->id][$condition->value] ?? 0,
                        'physical_qty' => null,
                        'difference' => 0,
                    ]);
                }
            }

            return $opname;
        });
    }

    /**
     * Simpan hasil hitung fisik. Bila $submit = true, stok sistem dipotret dan selisih dihitung.
     *
     * @param  array<int, int|null>  $physical  [stock_opname_item_id => jumlah fisik]
     */
    public function saveCount(StockOpname $opname, array $physical, ?string $note, bool $submit): void
    {
        if (! $opname->isDraft()) {
            throw new BusinessRuleException('Hanya opname berstatus draft yang bisa diubah.');
        }

        DB::transaction(function () use ($opname, $physical, $note, $submit) {
            $balances = $submit ? $this->stock->balances() : [];

            foreach ($opname->items as $item) {
                $qty = $physical[$item->id] ?? null;
                $item->physical_qty = $qty === null || $qty === '' ? null : (int) $qty;

                if ($submit) {
                    if ($item->physical_qty === null) {
                        throw new BusinessRuleException('Semua jumlah fisik wajib diisi sebelum diajukan.');
                    }
                    $item->system_qty = $balances[$item->cylinder_type_id][$item->condition->value] ?? 0;
                    $item->difference = $item->physical_qty - $item->system_qty;
                }

                $item->save();
            }

            $opname->note = $note;
            if ($submit) {
                $opname->status = OpnameStatus::Submitted;
                $opname->submitted_at = now();
            }
            $opname->save();

            if ($submit) {
                $this->notifications->opnameSubmitted($opname);
            }
        });
    }

    public function approve(StockOpname $opname, ?string $note = null): ?Adjustment
    {
        if (! $opname->isSubmitted()) {
            throw new BusinessRuleException('Opname ini belum diajukan atau sudah diproses.');
        }

        return DB::transaction(function () use ($opname, $note) {
            $adjustment = null;
            $differences = $opname->items->filter(fn ($item) => $item->difference !== 0);

            if ($differences->isNotEmpty()) {
                $adjustment = Adjustment::create([
                    'number' => $this->numbers->next(Adjustment::PREFIX, now()),
                    'date' => now()->toDateString(),
                    'type' => AdjustmentType::Opname,
                    'reason' => 'Selisih stok opname '.$opname->number,
                    'status' => ApprovalStatus::Approved,
                    'stock_opname_id' => $opname->id,
                    'user_id' => $opname->user_id,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'review_note' => $note,
                ]);

                foreach ($differences as $item) {
                    $adjustment->items()->create([
                        'cylinder_type_id' => $item->cylinder_type_id,
                        'from_condition' => $item->difference < 0 ? $item->condition : null,
                        'to_condition' => $item->difference > 0 ? $item->condition : null,
                        'qty' => abs($item->difference),
                    ]);
                }

                $this->adjustments->apply($adjustment->load('items'));
            }

            $opname->update([
                'status' => OpnameStatus::Approved,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'review_note' => $note,
            ]);

            $this->notifications->opnameReviewed($opname, approved: true);

            return $adjustment;
        });
    }

    public function reject(StockOpname $opname, string $note): void
    {
        if (! $opname->isSubmitted()) {
            throw new BusinessRuleException('Opname ini belum diajukan atau sudah diproses.');
        }

        $opname->update([
            'status' => OpnameStatus::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'review_note' => $note,
        ]);

        $this->notifications->opnameReviewed($opname, approved: false);
    }

    public function delete(StockOpname $opname): void
    {
        if (! $opname->isDraft()) {
            throw new BusinessRuleException('Hanya opname berstatus draft yang bisa dihapus.');
        }

        $opname->delete();
    }
}
