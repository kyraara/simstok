<?php

namespace App\Models;

use App\Enums\OpnameStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'number', 'date', 'status', 'user_id', 'submitted_at',
    'approved_by', 'approved_at', 'review_note', 'note',
])]
class StockOpname extends Model
{
    public const PREFIX = 'SO';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => OpnameStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function adjustment(): HasOne
    {
        return $this->hasOne(Adjustment::class);
    }

    public function isDraft(): bool
    {
        return $this->status === OpnameStatus::Draft;
    }

    public function isSubmitted(): bool
    {
        return $this->status === OpnameStatus::Submitted;
    }
}
