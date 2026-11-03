<?php

namespace App\Models;

use App\Enums\StockCondition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'weight_kg', 'min_stock', 'is_active'])]
class CylinderType extends Model
{
    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:1',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Saldo stok untuk kondisi tertentu (butuh relasi stocks sudah dimuat agar efisien). */
    public function stockOf(StockCondition|string $condition): int
    {
        $condition = $condition instanceof StockCondition ? $condition->value : $condition;

        return (int) $this->stocks->firstWhere('condition', StockCondition::from($condition))?->quantity;
    }

    /** "3 kg", "5,5 kg" */
    public function weightLabel(): string
    {
        $weight = (float) $this->weight_kg;

        return number_format($weight, floor($weight) == $weight ? 0 : 1, ',', '.').' kg';
    }

    public function isBelowMinimum(): bool
    {
        return $this->min_stock > 0 && $this->stockOf(StockCondition::Isi) < $this->min_stock;
    }
}
