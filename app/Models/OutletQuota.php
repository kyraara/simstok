<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['outlet_id', 'cylinder_type_id', 'monthly_quota'])]
class OutletQuota extends Model
{
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cylinderType(): BelongsTo
    {
        return $this->belongsTo(CylinderType::class);
    }
}
