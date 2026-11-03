<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDistributionRequest;
use App\Models\CylinderType;
use App\Models\Distribution;
use App\Models\Outlet;
use App\Models\Vehicle;
use App\Services\DistributionService;
use App\Services\DocumentNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Distribusi ke pangkalan (F-09, F-11, F-12, F-13). */
class DistributionController extends Controller
{
    public function __construct(private DistributionService $service) {}

    public function index(Request $request): View
    {
        $distributions = Distribution::query()
            ->with(['outlet', 'vehicle', 'items.cylinderType', 'user'])
            ->when($request->q, fn ($q, $s) => $q->where('number', 'like', "%{$s}%"))
            ->when($request->outlet_id, fn ($q, $id) => $q->where('outlet_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->from, fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->latest('date')->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('transactions.distributions.index', [
            'distributions' => $distributions,
            'outlets' => Outlet::orderBy('name')->get(),
        ]);
    }

    public function create(DocumentNumberService $numbers): View
    {
        return view('transactions.distributions.create', [
            'types' => CylinderType::active()->with('stocks')->orderBy('weight_kg')->get(),
            'outlets' => Outlet::active()->orderBy('name')->get(),
            'vehicles' => Vehicle::active()->orderBy('plate_number')->get(),
            'nextNumber' => $numbers->preview(Distribution::PREFIX),
        ]);
    }

    /** Kuota & realisasi bulan berjalan untuk pangkalan terpilih (dipakai form via fetch). */
    public function quota(Request $request): JsonResponse
    {
        $request->validate(['outlet_id' => ['required', 'integer'], 'date' => ['nullable', 'date']]);

        $outlet = Outlet::with('quotas')->findOrFail($request->outlet_id);
        $date = $request->date ? Carbon::parse($request->date) : now();

        return response()->json((object) $outlet->quotaUsage($date));
    }

    public function store(StoreDistributionRequest $request): RedirectResponse
    {
        $distribution = $this->service->create($request->headerData(), $request->lineItems());

        return redirect()
            ->route('distributions.show', $distribution)
            ->with('success', "Distribusi {$distribution->number} tersimpan. Stok sudah diperbarui.")
            ->with('print', $request->boolean('print'));
    }

    public function show(Distribution $distribution): View
    {
        $distribution->load(['items.cylinderType', 'outlet.quotas', 'vehicle', 'user', 'canceller']);

        return view('transactions.distributions.show', [
            'distribution' => $distribution,
            'usage' => $distribution->outlet->quotaUsage($distribution->date),
        ]);
    }

    /** F-12: cetak surat jalan (PDF). */
    public function print(Distribution $distribution): Response
    {
        $distribution->load(['items.cylinderType', 'outlet', 'vehicle', 'user']);

        return Pdf::loadView('pdf.surat-jalan', compact('distribution'))
            ->setPaper('a4')
            ->stream('surat-jalan-'.str_replace('/', '-', $distribution->number).'.pdf');
    }

    public function cancel(Request $request, Distribution $distribution): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        $this->service->cancel($distribution, $data['reason']);

        return redirect()->route('distributions.show', $distribution)->with('success', "Distribusi {$distribution->number} dibatalkan. Stok sudah dikembalikan.");
    }
}
