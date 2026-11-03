<?php

namespace App\Http\Controllers\Master;

use App\Enums\TransactionStatus;
use App\Models\CylinderType;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** F-06: CRUD pangkalan beserta kuota bulanan per jenis tabung. */
class OutletController extends MasterController
{
    public function index(Request $request): View
    {
        $outlets = Outlet::query()
            ->with('quotas.cylinderType')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
                ->orWhere('owner_name', 'like', "%{$s}%")))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('master.outlets.index', compact('outlets'));
    }

    public function show(Outlet $outlet): View
    {
        $outlet->load('quotas.cylinderType');

        return view('master.outlets.show', [
            'outlet' => $outlet,
            'types' => CylinderType::orderBy('weight_kg')->get(),
            'usage' => $outlet->quotaUsage(now()),
            'distributions' => $outlet->distributions()
                ->with('items.cylinderType')
                ->where('status', TransactionStatus::Posted)
                ->latest('date')->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Outlet(['is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $outlet = DB::transaction(function () use ($request) {
            [$data, $quotas] = $this->validated($request);
            $outlet = Outlet::create($data);
            $this->syncQuotas($outlet, $quotas);

            return $outlet;
        });

        return redirect()->route('master.outlets.show', $outlet)->with('success', "Pangkalan {$outlet->name} ditambahkan.");
    }

    public function edit(Outlet $outlet): View
    {
        return $this->form($outlet->load('quotas'));
    }

    public function update(Request $request, Outlet $outlet): RedirectResponse
    {
        DB::transaction(function () use ($request, $outlet) {
            [$data, $quotas] = $this->validated($request, $outlet);
            $outlet->update($data);
            $this->syncQuotas($outlet, $quotas);
        });

        return redirect()->route('master.outlets.show', $outlet)->with('success', "Pangkalan {$outlet->name} diperbarui.");
    }

    public function destroy(Outlet $outlet): RedirectResponse
    {
        return $this->deleteOrDeactivate($outlet, 'Pangkalan', 'master.outlets.index');
    }

    private function form(Outlet $outlet): View
    {
        return view('master.outlets.form', [
            'outlet' => $outlet,
            'types' => CylinderType::active()->orderBy('weight_kg')->get(),
            'quotas' => $outlet->quotas?->pluck('monthly_quota', 'cylinder_type_id') ?? collect(),
        ]);
    }

    private function validated(Request $request, ?Outlet $outlet = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:20', Rule::unique('outlets')->ignore($outlet)],
            'name' => ['required', 'string', 'max:150'],
            'owner_name' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'quotas' => ['nullable', 'array'],
            'quotas.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $quotas = $data['quotas'] ?? [];
        unset($data['quotas']);
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        return [$data, $quotas];
    }

    /** Simpan kuota > 0; kuota kosong/0 berarti pangkalan tidak menerima jenis tabung itu. */
    private function syncQuotas(Outlet $outlet, array $quotas): void
    {
        $validTypeIds = CylinderType::pluck('id')->all();

        foreach ($quotas as $typeId => $quota) {
            if (! in_array((int) $typeId, $validTypeIds)) {
                continue;
            }
            if ((int) $quota > 0) {
                $outlet->quotas()->updateOrCreate(['cylinder_type_id' => $typeId], ['monthly_quota' => (int) $quota]);
            } else {
                $outlet->quotas()->where('cylinder_type_id', $typeId)->delete();
            }
        }
    }
}
