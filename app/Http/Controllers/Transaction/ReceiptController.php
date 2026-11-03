<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptRequest;
use App\Models\CylinderType;
use App\Models\Receipt;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Services\DocumentNumberService;
use App\Services\ReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Penerimaan dari SPBE (F-08, F-13). */
class ReceiptController extends Controller
{
    public function __construct(private ReceiptService $service) {}

    public function index(Request $request): View
    {
        $receipts = Receipt::query()
            ->with(['supplier', 'vehicle', 'items.cylinderType', 'user'])
            ->when($request->q, fn ($q, $s) => $q->where(fn ($q) => $q->where('number', 'like', "%{$s}%")->orWhere('ref_document', 'like', "%{$s}%")))
            ->when($request->supplier_id, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->from, fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->latest('date')->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('transactions.receipts.index', [
            'receipts' => $receipts,
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function create(DocumentNumberService $numbers): View
    {
        return view('transactions.receipts.create', [
            'types' => CylinderType::active()->with('stocks')->orderBy('weight_kg')->get(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'vehicles' => Vehicle::active()->orderBy('plate_number')->get(),
            'nextNumber' => $numbers->preview(Receipt::PREFIX),
        ]);
    }

    public function store(StoreReceiptRequest $request): RedirectResponse
    {
        $receipt = $this->service->create($request->headerData(), $request->lineItems());

        return redirect()->route('receipts.show', $receipt)->with('success', "Penerimaan {$receipt->number} tersimpan. Stok sudah diperbarui.");
    }

    public function show(Receipt $receipt): View
    {
        $receipt->load(['items.cylinderType', 'supplier', 'vehicle', 'user', 'canceller']);

        return view('transactions.receipts.show', compact('receipt'));
    }

    public function cancel(Request $request, Receipt $receipt): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        $this->service->cancel($receipt, $data['reason']);

        return redirect()->route('receipts.show', $receipt)->with('success', "Penerimaan {$receipt->number} dibatalkan. Stok sudah dikembalikan.");
    }
}
