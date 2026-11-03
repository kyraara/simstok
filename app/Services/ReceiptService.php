<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\StockCondition;
use App\Enums\TransactionStatus;
use App\Models\Receipt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BusinessRuleException;

/** Penerimaan dari SPBE: tabung isi masuk (+), tabung kosong dikirim ke SPBE (−). */
class ReceiptService
{
    public function __construct(
        private StockService $stock,
        private DocumentNumberService $numbers,
    ) {}

    /**
     * @param  array{date: string, ref_document?: ?string, supplier_id: int, vehicle_id?: ?int, note?: ?string}  $data
     * @param  array<int, array{cylinder_type_id: int, qty_full_in: int, qty_empty_out: int}>  $items
     */
    public function create(array $data, array $items): Receipt
    {
        return DB::transaction(function () use ($data, $items) {
            $receipt = Receipt::create([
                ...$data,
                'number' => $this->numbers->next(Receipt::PREFIX, Carbon::parse($data['date'])),
                'user_id' => auth()->id(),
                'status' => TransactionStatus::Posted,
            ]);

            foreach ($items as $item) {
                $receipt->items()->create($item);

                $this->stock->move($item['cylinder_type_id'], StockCondition::Isi, (int) $item['qty_full_in'], $receipt, MovementType::Penerimaan);
                $this->stock->move($item['cylinder_type_id'], StockCondition::Kosong, -(int) $item['qty_empty_out'], $receipt, MovementType::Penerimaan);
            }

            return $receipt;
        });
    }

    public function cancel(Receipt $receipt, string $reason): void
    {
        if (! $receipt->isPosted()) {
            throw new BusinessRuleException('Penerimaan ini sudah dibatalkan.');
        }

        DB::transaction(function () use ($receipt, $reason) {
            $this->stock->reverse($receipt, 'Batal '.$receipt->number.': '.$reason);

            $receipt->update([
                'status' => TransactionStatus::Cancelled,
                'cancel_reason' => $reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
            ]);
        });
    }
}
