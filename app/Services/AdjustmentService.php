<?php

namespace App\Services;

use App\Enums\AdjustmentType;
use App\Enums\ApprovalStatus;
use App\Enums\MovementType;
use App\Enums\StockCondition;
use App\Exceptions\BusinessRuleException;
use App\Models\Adjustment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Penyesuaian stok. Dibuat petugas dengan status "pending";
 * stok baru berubah setelah disetujui Pimpinan/Admin.
 */
class AdjustmentService
{
    public function __construct(
        private StockService $stock,
        private DocumentNumberService $numbers,
        private NotificationService $notifications,
    ) {}

    /**
     * Ubah baris form (kondisi + arah) menjadi from/to_condition.
     *
     * @param  array{cylinder_type_id: int, condition: string, qty: int, direction?: ?string}  $row
     * @return array{cylinder_type_id: int, from_condition: ?StockCondition, to_condition: ?StockCondition, qty: int}
     */
    public function mapItem(AdjustmentType $type, array $row): array
    {
        $condition = StockCondition::from($row['condition']);

        [$from, $to] = match ($type) {
            AdjustmentType::Rusak => [$condition, StockCondition::Rusak],
            AdjustmentType::Hilang => [$condition, null],
            AdjustmentType::Koreksi => ($row['direction'] ?? 'minus') === 'plus' ? [null, $condition] : [$condition, null],
            AdjustmentType::Opname => throw new BusinessRuleException('Penyesuaian opname dibuat otomatis dari stok opname.'),
        };

        return [
            'cylinder_type_id' => (int) $row['cylinder_type_id'],
            'from_condition' => $from,
            'to_condition' => $to,
            'qty' => (int) $row['qty'],
        ];
    }

    /**
     * @param  array{date: string, type: string, reason: string}  $data
     * @param  array<int, array{cylinder_type_id: int, condition: string, qty: int, direction?: ?string}>  $rows
     */
    public function create(array $data, array $rows): Adjustment
    {
        $type = AdjustmentType::from($data['type']);
        $items = array_map(fn ($row) => $this->mapItem($type, $row), $rows);

        // Cek awal supaya petugas langsung tahu bila jumlah melebihi stok.
        foreach ($this->outgoingTotals($items) as $key => $qty) {
            [$typeId, $condition] = explode('|', $key);
            $this->stock->assertAvailable((int) $typeId, StockCondition::from($condition), $qty);
        }

        return DB::transaction(function () use ($data, $type, $items) {
            $adjustment = Adjustment::create([
                'number' => $this->numbers->next(Adjustment::PREFIX, Carbon::parse($data['date'])),
                'date' => $data['date'],
                'type' => $type,
                'reason' => $data['reason'],
                'status' => ApprovalStatus::Pending,
                'user_id' => auth()->id(),
            ]);

            $adjustment->items()->createMany($items);

            $this->notifications->adjustmentSubmitted($adjustment);

            return $adjustment;
        });
    }

    public function approve(Adjustment $adjustment, ?string $note = null): void
    {
        $this->ensurePending($adjustment);

        DB::transaction(function () use ($adjustment, $note) {
            $this->apply($adjustment);

            $adjustment->update([
                'status' => ApprovalStatus::Approved,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'review_note' => $note,
            ]);

            $this->notifications->adjustmentReviewed($adjustment, approved: true);
        });
    }

    public function reject(Adjustment $adjustment, string $note): void
    {
        $this->ensurePending($adjustment);

        $adjustment->update([
            'status' => ApprovalStatus::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'review_note' => $note,
        ]);

        $this->notifications->adjustmentReviewed($adjustment, approved: false);
    }

    public function cancel(Adjustment $adjustment): void
    {
        $this->ensurePending($adjustment);

        $adjustment->update(['status' => ApprovalStatus::Cancelled]);

        $this->notifications->resolveTask(route('adjustments.show', $adjustment));
    }

    /** Terapkan item penyesuaian ke stok: from_condition berkurang, to_condition bertambah. */
    public function apply(Adjustment $adjustment): void
    {
        $movementType = $adjustment->type === AdjustmentType::Opname ? MovementType::Opname : MovementType::Penyesuaian;
        $note = $adjustment->type->label().': '.$adjustment->reason;

        DB::transaction(function () use ($adjustment, $movementType, $note) {
            foreach ($adjustment->items as $item) {
                if ($item->from_condition) {
                    $this->stock->move($item->cylinder_type_id, $item->from_condition, -$item->qty, $adjustment, $movementType, $note);
                }
                if ($item->to_condition) {
                    $this->stock->move($item->cylinder_type_id, $item->to_condition, $item->qty, $adjustment, $movementType, $note);
                }
            }
        });
    }

    private function ensurePending(Adjustment $adjustment): void
    {
        if (! $adjustment->isPending()) {
            throw new BusinessRuleException('Penyesuaian ini sudah diproses ('.$adjustment->status->label().').');
        }
    }

    /** @return array<string, int> total jumlah keluar per "typeId|kondisi" */
    private function outgoingTotals(array $items): array
    {
        $totals = [];
        foreach ($items as $item) {
            if ($item['from_condition']) {
                $key = $item['cylinder_type_id'].'|'.$item['from_condition']->value;
                $totals[$key] = ($totals[$key] ?? 0) + $item['qty'];
            }
        }

        return $totals;
    }
}
