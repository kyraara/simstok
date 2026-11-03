<?php

namespace App\Http\Controllers\Master;

use App\Models\CylinderType;
use App\Models\OutletQuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** F-04: CRUD jenis tabung (kode, nama, berat, stok minimum). */
class CylinderTypeController extends MasterController
{
    public function index(): View
    {
        return view('master.cylinder-types.index', [
            'types' => CylinderType::with('stocks')->orderBy('weight_kg')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.cylinder-types.form', ['type' => new CylinderType(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = CylinderType::create($this->validated($request));

        return redirect()->route('master.cylinder-types.index')->with('success', "Jenis tabung {$type->name} ditambahkan.");
    }

    public function edit(CylinderType $cylinderType): View
    {
        return view('master.cylinder-types.form', ['type' => $cylinderType]);
    }

    public function update(Request $request, CylinderType $cylinderType): RedirectResponse
    {
        $cylinderType->update($this->validated($request, $cylinderType));

        return redirect()->route('master.cylinder-types.index')->with('success', "Jenis tabung {$cylinderType->name} diperbarui.");
    }

    public function destroy(CylinderType $cylinderType): RedirectResponse
    {
        if ($cylinderType->movements()->exists() || OutletQuota::where('cylinder_type_id', $cylinderType->id)->exists()) {
            return back()->withErrors(['general' => 'Jenis tabung ini sudah dipakai (riwayat mutasi atau kuota pangkalan). Nonaktifkan saja.']);
        }

        DB::transaction(function () use ($cylinderType) {
            $cylinderType->stocks()->delete();
            $cylinderType->delete();
        });

        return redirect()->route('master.cylinder-types.index')->with('success', 'Jenis tabung berhasil dihapus.');
    }

    private function validated(Request $request, ?CylinderType $type = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:20', Rule::unique('cylinder_types')->ignore($type)],
            'name' => ['required', 'string', 'max:100'],
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:9999'],
            'min_stock' => ['required', 'integer', 'min:0'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
