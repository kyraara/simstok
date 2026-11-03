<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'owner_name', 'address', 'phone', 'is_active'])]
class Outlet extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function quotas(): HasMany
    {
        return $this->hasMany(OutletQuota::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Kuota dan realisasi bulan dari tanggal tertentu, per jenis tabung.
     *
     * @return array<int, array{quota: int, used: int}> dikunci cylinder_type_id
     */
    public function quotaUsage(CarbonInterface $date, ?int $exceptDistributionId = null): array
    {
        $used = DistributionItem::query()
            ->join('distributions', 'distributions.id', '=', 'distribution_items.distribution_id')
            ->where('distributions.outlet_id', $this->id)
            ->where('distributions.status', TransactionStatus::Posted->value)
            ->whereBetween('distributions.date', [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()])
            ->when($exceptDistributionId, fn ($q) => $q->where('distributions.id', '!=', $exceptDistributionId))
            ->groupBy('distribution_items.cylinder_type_id')
            ->selectRaw('distribution_items.cylinder_type_id, SUM(distribution_items.qty_full_out) as total')
            ->pluck('total', 'cylinder_type_id');

        $result = [];
        foreach ($this->quotas as $quota) {
            $result[$quota->cylinder_type_id] = [
                'quota' => (int) $quota->monthly_quota,
                'used' => (int) ($used[$quota->cylinder_type_id] ?? 0),
            ];
        }

        return $result;
    }
}
