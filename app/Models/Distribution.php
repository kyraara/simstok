<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Models\Concerns\Cancellable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Distribusi tabung isi ke pangkalan (sekaligus penerimaan tabung kosong dari pangkalan). */
#[Fillable([
    'number', 'date', 'outlet_id', 'vehicle_id', 'user_id',
    'status', 'cancel_reason', 'cancelled_by', 'cancelled_at', 'note',
])]
class Distribution extends Model
{
    use Cancellable;

    public const PREFIX = 'SJ';

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
        return $this->hasMany(DistributionItem::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
