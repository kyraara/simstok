<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Models\Concerns\Cancellable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Penerimaan tabung isi dari SPBE (sekaligus pengiriman tabung kosong ke SPBE). */
#[Fillable([
    'number', 'ref_document', 'date', 'supplier_id', 'vehicle_id', 'user_id',
    'status', 'cancel_reason', 'cancelled_by', 'cancelled_at', 'note',
])]
class Receipt extends Model
{
    use Cancellable;

    public const PREFIX = 'PN';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => TransactionStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
