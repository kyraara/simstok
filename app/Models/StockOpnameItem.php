<?php

namespace App\Models;

use App\Enums\StockCondition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['stock_opname_id', 'cylinder_type_id', 'condition', 'system_qty', 'physical_qty', 'difference'])]
class StockOpnameItem extends Model
{
    protected function casts(): array
    {
        return [
            'condition' => StockCondition::class,
            'system_qty' => 'integer',
            'physical_qty' => 'integer',
            'difference' => 'integer',
        ];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function cylinderType(): BelongsTo
    {
        return $this->belongsTo(CylinderType::class);
    }
}
