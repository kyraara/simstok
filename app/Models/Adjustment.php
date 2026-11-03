<?php

namespace App\Models;

use App\Enums\AdjustmentType;
use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Penyesuaian stok: rusak, hilang, koreksi, atau hasil stok opname. */
#[Fillable([
    'number', 'date', 'type', 'reason', 'status', 'stock_opname_id',
    'user_id', 'approved_by', 'approved_at', 'review_note',
])]
class Adjustment extends Model
{
    public const PREFIX = 'PY';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => AdjustmentType::class,
            'status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AdjustmentItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }
}
