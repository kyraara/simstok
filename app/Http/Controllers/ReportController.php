<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportsReports;
use App\Models\Outlet;
use App\Models\Supplier;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Laporan F-22, F-23, F-25 (layar, PDF, Excel). F-21 di StockCardController, F-24 di StockOpnameController. */
class ReportController extends Controller
{
    use ExportsReports;

    public function __construct(private ReportService $reports) {}

    public function index(): View
    {
        return view('reports.index');
    }

    public function receipts(Request $request): View|Response
    {
        $request->validate(['supplier_id' => ['nullable', 'integer']]);
        [$from, $to] = $this->period($request);
        $report = $this->reports->receipts($from, $to, $request->integer('supplier_id') ?: null);

        return $this->export($request, $report) ?? view('reports.table', [
            'report' => $report,
            'route' => 'reports.receipts',
            'filters' => ['period' => [$from, $to], 'select' => ['name' => 'supplier_id', 'label' => 'SPBE', 'all' => 'Semua SPBE', 'options' => Supplier::orderBy('name')->pluck('name', 'id')]],
        ]);
    }

    public function distributions(Request $request): View|Response
    {
        $request->validate(['outlet_id' => ['nullable', 'integer']]);
        [$from, $to] = $this->period($request);
        $report = $this->reports->distributions($from, $to, $request->integer('outlet_id') ?: null);

        return $this->export($request, $report) ?? view('reports.table', [
            'report' => $report,
            'route' => 'reports.distributions',
            'filters' => ['period' => [$from, $to], 'select' => ['name' => 'outlet_id', 'label' => 'Pangkalan', 'all' => 'Semua pangkalan', 'options' => Outlet::orderBy('name')->pluck('name', 'id')]],
        ]);
    }

    public function stockPosition(Request $request): View|Response
    {
        $request->validate(['date' => ['nullable', 'date', 'before_or_equal:today']]);
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        $report = $this->reports->stockPosition($date);

        return $this->export($request, $report) ?? view('reports.table', [
            'report' => $report,
            'route' => 'reports.stock-position',
            'filters' => ['date' => $date],
        ]);
    }
}
