<?php

namespace App\Http\Controllers;

use App\Models\StockOpname;
use App\Services\StockOpnameService;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Stok opname (F-14, F-15, F-16, F-24). */
class StockOpnameController extends Controller
{
    public function __construct(private StockOpnameService $service) {}

    public function index(): View
    {
        return view('opname.index', [
            'opnames' => StockOpname::with(['user', 'approver', 'items'])->latest('date')->latest('id')->paginate(15),
            'open' => $this->service->openSession(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $opname = $this->service->create($data['date'], $data['note'] ?? null);

        return redirect()->route('stock-opnames.show', $opname)->with('success', "Sesi {$opname->number} dibuat. Silakan isi hasil hitung fisik.");
    }

    public function show(StockOpname $stockOpname, StockService $stock): View
    {
        $stockOpname->load(['items.cylinderType', 'user', 'approver', 'adjustment']);

        return view('opname.show', [
            'opname' => $stockOpname,
            'items' => $stockOpname->items->groupBy('cylinder_type_id'),
            'balances' => $stock->balances(),
        ]);
    }

    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        $data = $request->validate([
            'physical' => ['array'],
            'physical.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $submit = $request->input('action') === 'submit';
        $this->service->saveCount($stockOpname->load('items'), $data['physical'] ?? [], $data['note'] ?? null, $submit);

        return redirect()->route('stock-opnames.show', $stockOpname)->with('success', $submit
            ? "Hasil hitung {$stockOpname->number} diajukan. Menunggu persetujuan Pimpinan."
            : 'Hasil hitung disimpan sebagai draft.');
    }

    public function destroy(StockOpname $stockOpname): RedirectResponse
    {
        $this->service->delete($stockOpname);

        return redirect()->route('stock-opnames.index')->with('success', "Draft {$stockOpname->number} dihapus.");
    }

    public function approve(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:255']]);

        $adjustment = $this->service->approve($stockOpname->load('items'), $data['review_note'] ?? null);

        return back()->with('success', $adjustment
            ? "Opname disetujui. Selisih dibukukan sebagai penyesuaian {$adjustment->number}."
            : 'Opname disetujui. Tidak ada selisih stok.');
    }

    public function reject(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        $this->service->reject($stockOpname, $data['reason']);

        return back()->with('success', "Opname {$stockOpname->number} ditolak.");
    }

    /** F-24: laporan stok opname & selisih. */
    public function pdf(StockOpname $stockOpname): Response
    {
        $stockOpname->load(['items.cylinderType', 'user', 'approver', 'adjustment']);

        return Pdf::loadView('pdf.opname', ['opname' => $stockOpname])
            ->setPaper('a4')
            ->stream('stok-opname-'.str_replace('/', '-', $stockOpname->number).'.pdf');
    }
}
