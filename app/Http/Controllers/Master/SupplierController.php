<?php

namespace App\Http\Controllers\Master;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** F-05: CRUD pemasok / SPBE. */
class SupplierController extends MasterController
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")))
            ->withCount('receipts')
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('master.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('master.suppliers.form', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()->route('master.suppliers.index')->with('success', "SPBE {$supplier->name} ditambahkan.");
    }

    public function edit(Supplier $supplier): View
    {
        return view('master.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('master.suppliers.index')->with('success', "SPBE {$supplier->name} diperbarui.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        return $this->deleteOrDeactivate($supplier, 'SPBE', 'master.suppliers.index');
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:20', Rule::unique('suppliers')->ignore($supplier)],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
