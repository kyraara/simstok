<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\StockCondition;
use App\Enums\TransactionStatus;
use App\Models\Distribution;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BusinessRuleException;

/** Distribusi ke pangkalan: tabung isi keluar (−), tabung kosong kembali (+). */
class DistributionService
{
    public function __construct(
        private StockService $stock,
        private DocumentNumberService $numbers,
    ) {}

    /**
     * @param  array{date: string, outlet_id: int, vehicle_id?: ?int, note?: ?string}  $data
     * @param  array<int, array{cylinder_type_id: int, qty_full_out: int, qty_empty_in: int}>  $items
     */
    public function create(array $data, array $items): Distribution
    {
        return DB::transaction(function () use ($data, $items) {
            $distribution = Distribution::create([
                ...$data,
                'number' => $this->numbers->next(Distribution::PREFIX, Carbon::parse($data['date'])),
                'user_id' => auth()->id(),
                'status' => TransactionStatus::Posted,
            ]);

            foreach ($items as $item) {
                $distribution->items()->create($item);

                $this->stock->move($item['cylinder_type_id'], StockCondition::Isi, -(int) $item['qty_full_out'], $distribution, MovementType::Distribusi);
                $this->stock->move($item['cylinder_type_id'], StockCondition::Kosong, (int) $item['qty_empty_in'], $distribution, MovementType::Distribusi);
            }

            return $distribution;
        });
    }

    public function cancel(Distribution $distribution, string $reason): void
    {
        if (! $distribution->isPosted()) {
            throw new BusinessRuleException('Distribusi ini sudah dibatalkan.');
        }

        DB::transaction(function () use ($distribution, $reason) {
            $this->stock->reverse($distribution, 'Batal '.$distribution->number.': '.$reason);

            $distribution->update([
                'status' => TransactionStatus::Cancelled,
                'cancel_reason' => $reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
            ]);
        });
    }
}
