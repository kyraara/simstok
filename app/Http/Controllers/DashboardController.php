<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\OpnameStatus;
use App\Enums\TransactionStatus;
use App\Models\Adjustment;
use App\Models\CylinderType;
use App\Models\Distribution;
use App\Models\DistributionItem;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\StockOpname;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Dashboard (F-17 s.d. F-20). */
class DashboardController extends Controller
{
    /** Warna seri grafik per urutan jenis tabung. */
    private const SERIES_COLORS = ['#2E7D4F', '#14213D', '#F2A541', '#8A94A6', '#E4572E', '#5B8DB8'];

    public function __invoke(): View
    {
        $types = CylinderType::active()->with('stocks')->orderBy('weight_kg')->get();

        return view('dashboard', [
            'types' => $types,
            'lowStock' => $types->filter->isBelowMinimum(),
            'chart' => $this->distributionChart($types),
            'today' => $this->todaySummary(),
            'recent' => $this->recentTransactions(),
            'pendingAdjustments' => Adjustment::with('user')->where('status', ApprovalStatus::Pending)->latest()->get(),
            'pendingOpnames' => StockOpname::with('user')->where('status', OpnameStatus::Submitted)->latest()->get(),
        ]);
    }

    /** F-19: total tabung isi yang didistribusikan per hari selama 30 hari terakhir, per jenis. */
    private function distributionChart(Collection $types): array
    {
        $start = now()->subDays(29)->startOfDay();

        $rows = DistributionItem::query()
            ->join('distributions', 'distributions.id', '=', 'distribution_items.distribution_id')
            ->where('distributions.status', TransactionStatus::Posted->value)
            ->where('distributions.date', '>=', $start->toDateString())
            ->groupBy('distributions.date', 'distribution_items.cylinder_type_id')
            ->selectRaw('distributions.date as day, distribution_items.cylinder_type_id as type_id, SUM(distribution_items.qty_full_out) as total')
            ->get();

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[Carbon::parse($row->day)->toDateString()][$row->type_id] = (int) $row->total;
        }

        $days = collect(range(0, 29))->map(fn ($i) => $start->copy()->addDays($i));

        return [
            'labels' => $days->map(fn ($d) => $d->translatedFormat('d M'))->all(),
            'datasets' => $types->values()->map(fn ($type, $i) => [
                'label' => $type->name,
                'data' => $days->map(fn ($d) => $byDay[$d->toDateString()][$type->id] ?? 0)->all(),
                'backgroundColor' => self::SERIES_COLORS[$i % count(self::SERIES_COLORS)],
                'borderRadius' => 2,
                'maxBarThickness' => 22,
            ])->filter(fn ($ds) => array_sum($ds['data']) > 0)->values()->all(),
            'total' => array_sum(array_map(fn ($day) => array_sum($day), $byDay)),
        ];
    }

    private function todaySummary(): array
    {
        $today = now()->toDateString();

        return [
            'distributions' => Distribution::posted()->whereDate('date', $today)->count(),
            'full_out' => (int) DistributionItem::whereHas('distribution', fn ($q) => $q->posted()->whereDate('date', $today))->sum('qty_full_out'),
            'receipts' => Receipt::posted()->whereDate('date', $today)->count(),
            'full_in' => (int) ReceiptItem::whereHas('receipt', fn ($q) => $q->posted()->whereDate('date', $today))->sum('qty_full_in'),
        ];
    }

    /** F-20: gabungan transaksi terbaru dari penerimaan, distribusi, dan penyesuaian. */
    private function recentTransactions(): Collection
    {
        $receipts = Receipt::with(['supplier', 'items'])->latest('id')->limit(8)->get()->map(fn ($r) => [
            'kind' => 'Penerimaan',
            'icon' => 'receipt',
            'number' => $r->number,
            'party' => $r->supplier->name,
            'qty' => '+'.number_format($r->items->sum('qty_full_in'), 0, ',', '.').' isi',
            'status' => $r->status,
            'at' => $r->created_at,
            'url' => route('receipts.show', $r),
        ]);

        $distributions = Distribution::with(['outlet', 'items'])->latest('id')->limit(8)->get()->map(fn ($d) => [
            'kind' => 'Distribusi',
            'icon' => 'distribution',
            'number' => $d->number,
            'party' => $d->outlet->name,
            'qty' => '−'.number_format($d->items->sum('qty_full_out'), 0, ',', '.').' isi',
            'status' => $d->status,
            'at' => $d->created_at,
            'url' => route('distributions.show', $d),
        ]);

        $adjustments = Adjustment::with('items')->latest('id')->limit(8)->get()->map(fn ($a) => [
            'kind' => 'Penyesuaian',
            'icon' => 'adjustment',
            'number' => $a->number,
            'party' => $a->type->label(),
            'qty' => number_format($a->items->sum('qty'), 0, ',', '.').' tabung',
            'status' => $a->status,
            'at' => $a->created_at,
            'url' => route('adjustments.show', $a),
        ]);

        return $receipts->concat($distributions)->concat($adjustments)->sortByDesc('at')->take(8)->values();
    }
}
