<?php

namespace App\Http\Controllers\Master;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** F-07: CRUD kendaraan & sopir. */
class VehicleController extends MasterController
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($q) => $q->where('plate_number', 'like', "%{$s}%")->orWhere('driver_name', 'like', "%{$s}%")))
            ->orderBy('plate_number')
            ->paginate(15)
            ->withQueryString();

        return view('master.vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        return view('master.vehicles.form', ['vehicle' => new Vehicle(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vehicle = Vehicle::create($this->validated($request));

        return redirect()->route('master.vehicles.index')->with('success', "Kendaraan {$vehicle->plate_number} ditambahkan.");
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('master.vehicles.form', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validated($request, $vehicle));

        return redirect()->route('master.vehicles.index')->with('success', "Kendaraan {$vehicle->plate_number} diperbarui.");
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        return $this->deleteOrDeactivate($vehicle, 'Kendaraan', 'master.vehicles.index');
    }

    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $request->merge(['plate_number' => strtoupper(preg_replace('/\s+/', ' ', trim((string) $request->plate_number)))]);

        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles')->ignore($vehicle)],
            'type' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
