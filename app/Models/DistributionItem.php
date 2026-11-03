<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['distribution_id', 'cylinder_type_id', 'qty_full_out', 'qty_empty_in'])]
class DistributionItem extends Model
{
    protected function casts(): array
    {
        return ['qty_full_out' => 'integer', 'qty_empty_in' => 'integer'];
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function cylinderType(): BelongsTo
    {
        return $this->belongsTo(CylinderType::class);
    }
}
