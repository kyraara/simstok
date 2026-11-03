<?php

namespace App\Models;

use App\Enums\StockCondition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['adjustment_id', 'cylinder_type_id', 'from_condition', 'to_condition', 'qty'])]
class AdjustmentItem extends Model
{
    protected function casts(): array
    {
        return [
            'from_condition' => StockCondition::class,
            'to_condition' => StockCondition::class,
            'qty' => 'integer',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(Adjustment::class);
    }

    public function cylinderType(): BelongsTo
    {
        return $this->belongsTo(CylinderType::class);
    }

    /** Deskripsi singkat arah perpindahan, mis. "Isi → Rusak" atau "Kosong −". */
    public function directionLabel(): string
    {
        return match (true) {
            $this->from_condition && $this->to_condition => $this->from_condition->label().' → '.$this->to_condition->label(),
            (bool) $this->from_condition => $this->from_condition->label().' berkurang',
            (bool) $this->to_condition => $this->to_condition->label().' bertambah',
            default => '-',
        };
    }
}
