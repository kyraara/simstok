<?php

namespace App\Models;

use App\Enums\StockCondition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saldo stok terkini. JANGAN diubah langsung — gunakan App\Services\StockService.
 */
#[Fillable(['cylinder_type_id', 'condition', 'quantity'])]
class Stock extends Model
{
    protected function casts(): array
    {
        return [
            'condition' => StockCondition::class,
            'quantity' => 'integer',
        ];
    }

    public function cylinderType(): BelongsTo
    {
        return $this->belongsTo(CylinderType::class);
    }
}
