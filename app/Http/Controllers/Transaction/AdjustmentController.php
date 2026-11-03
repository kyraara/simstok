<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\AdjustmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Models\Adjustment;
use App\Models\CylinderType;
use App\Services\AdjustmentService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Penyesuaian stok (F-10) dengan persetujuan Pimpinan. */
class AdjustmentController extends Controller
{
    public function __construct(private AdjustmentService $service) {}

    public function index(Request $request): View
    {
        $adjustments = Adjustment::query()
            ->with(['items.cylinderType', 'user', 'approver'])
            ->when($request->q, fn ($q, $s) => $q->where(fn ($q) => $q->where('number', 'like', "%{$s}%")->orWhere('reason', 'like', "%{$s}%")))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('date')->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('transactions.adjustments.index', compact('adjustments'));
    }

    public function create(StockService $stock): View
    {
        return view('transactions.adjustments.create', [
            'types' => CylinderType::active()->orderBy('weight_kg')->get(),
            'adjustmentTypes' => AdjustmentType::manual(),
            'stocks' => $stock->balances(),
        ]);
    }

    public function store(StoreAdjustmentRequest $request): RedirectResponse
    {
        $adjustment = $this->service->create($request->safe()->only(['date', 'type', 'reason']), $request->validated('items'));

        return redirect()->route('adjustments.show', $adjustment)
            ->with('success', "Penyesuaian {$adjustment->number} tersimpan dan menunggu persetujuan. Stok belum berubah sampai disetujui.");
    }

    public function show(Adjustment $adjustment): View
    {
        $adjustment->load(['items.cylinderType', 'user', 'approver', 'stockOpname']);

        return view('transactions.adjustments.show', compact('adjustment'));
    }

    public function approve(Request $request, Adjustment $adjustment): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:255']]);

        $this->service->approve($adjustment->load('items'), $data['review_note'] ?? null);

        return back()->with('success', "Penyesuaian {$adjustment->number} disetujui. Stok sudah diperbarui.");
    }

    public function reject(Request $request, Adjustment $adjustment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        $this->service->reject($adjustment, $data['reason']);

        return back()->with('success', "Penyesuaian {$adjustment->number} ditolak.");
    }

    public function cancel(Request $request, Adjustment $adjustment): RedirectResponse
    {
        abort_unless($adjustment->user_id === $request->user()->id || $request->user()->hasRole('admin'), 403);

        $this->service->cancel($adjustment);

        return back()->with('success', "Penyesuaian {$adjustment->number} dibatalkan.");
    }
}
