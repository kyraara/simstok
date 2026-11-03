<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['plate_number', 'type', 'driver_name', 'is_active'])]
class Vehicle extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function label(): string
    {
        return trim($this->plate_number.($this->driver_name ? ' — '.$this->driver_name : ''));
    }
}
