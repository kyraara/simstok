<?php

namespace App\Models;

use App\Enums\MovementType;
use App\Enums\StockCondition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'cylinder_type_id', 'condition', 'type', 'qty', 'balance_after',
    'reference_type', 'reference_id', 'user_id', 'note', 'moved_at',
])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'condition' => StockCondition::class,
            'type' => MovementType::class,
            'qty' => 'integer',
            'balance_after' => 'integer',
            'moved_at' => 'datetime',
        ];
    }

    public function cylinderType(): BelongsTo
    {
        return $this->belongsTo(CylinderType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
