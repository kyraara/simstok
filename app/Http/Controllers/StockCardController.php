<?php

namespace App\Http\Controllers;

use App\Enums\StockCondition;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Models\CylinderType;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/** F-21: kartu stok (mutasi per jenis tabung & kondisi). */
class StockCardController extends Controller
{
    use ExportsReports;

    public function __invoke(Request $request, ReportService $reports): mixed
    {
        $request->validate([
            'cylinder_type_id' => ['nullable', 'integer', 'exists:cylinder_types,id'],
            'condition' => ['nullable', Rule::enum(StockCondition::class)],
        ]);

        $types = CylinderType::with('stocks')->orderBy('weight_kg')->get();
        $type = $types->firstWhere('id', (int) $request->cylinder_type_id) ?? $types->first();
        $condition = StockCondition::tryFrom((string) $request->condition) ?? StockCondition::Isi;
        [$from, $to] = $this->period($request);

        abort_unless($type, 404, 'Belum ada jenis tabung.');

        if ($request->filled('export')) {
            $response = $this->export($request, $reports->stockCardTable($type, $condition, $from, $to));
            if ($response instanceof Response) {
                return $response;
            }
        }

        return view('reports.stock-card', [
            'types' => $types,
            'type' => $type,
            'condition' => $condition,
            'from' => $from,
            'to' => $to,
            'card' => $reports->stockCard($type->id, $condition, $from, $to),
        ]);
    }
}
