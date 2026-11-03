<?php

namespace App\Models\Concerns;

use App\Enums\TransactionStatus;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Perilaku bersama transaksi penerimaan & distribusi: pencatat, pembatal, mutasi stok. */
trait Cancellable
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isPosted(): bool
    {
        return $this->status === TransactionStatus::Posted;
    }

    public function scopePosted(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), TransactionStatus::Posted->value);
    }
}
