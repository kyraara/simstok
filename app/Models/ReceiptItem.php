<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['receipt_id', 'cylinder_type_id', 'qty_full_in', 'qty_empty_out'])]
class ReceiptItem extends Model
{
    protected function casts(): array
    {
        return ['qty_full_in' => 'integer', 'qty_empty_out' => 'integer'];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function cylinderType(): BelongsTo
    {
        return $this->belongsTo(CylinderType::class);
    }
}
