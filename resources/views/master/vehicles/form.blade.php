@php $editing = $vehicle->exists; @endphp
<x-layouts.app :title="$editing ? 'Ubah Kendaraan' : 'Tambah Kendaraan'">
    <x-page-header :title="$editing ? 'Ubah Kendaraan' : 'Tambah Kendaraan'" :back="route('master.vehicles.index')" />

    <form method="POST" action="{{ $editing ? route('master.vehicles.update', $vehicle) : route('master.vehicles.store') }}" class="card max-w-2xl p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="No. polisi" name="plate_number" required>
                <input id="plate_number" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" required maxlength="20" class="input uppercase @error('plate_number') input-error @enderror" placeholder="BG 1234 XX">
            </x-field>
            <x-field label="Jenis kendaraan" name="type">
                <input id="type" name="type" value="{{ old('type', $vehicle->type) }}" maxlength="50" class="input @error('type') input-error @enderror" placeholder="Truk Engkel">
            </x-field>
            <x-field label="Nama sopir" name="driver_name" class="sm:col-span-2">
                <input id="driver_name" name="driver_name" value="{{ old('driver_name', $vehicle->driver_name) }}" maxlength="100" class="input @error('driver_name') input-error @enderror">
            </x-field>
            <div class="sm:col-span-2"><x-active-toggle :checked="$vehicle->is_active" /></div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('master.vehicles.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button>
        </div>
    </form>
</x-layouts.app>
