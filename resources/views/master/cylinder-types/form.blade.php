@php $editing = $type->exists; @endphp
<x-layouts.app :title="$editing ? 'Ubah Jenis Tabung' : 'Tambah Jenis Tabung'">
    <x-page-header :title="$editing ? 'Ubah Jenis Tabung' : 'Tambah Jenis Tabung'" :back="route('master.cylinder-types.index')" />

    <form method="POST" action="{{ $editing ? route('master.cylinder-types.update', $type) : route('master.cylinder-types.store') }}" class="card max-w-2xl p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Kode" name="code" required hint="Contoh: LPG3, BG55, LPG12">
                <input id="code" name="code" value="{{ old('code', $type->code) }}" required maxlength="20" class="input uppercase @error('code') input-error @enderror">
            </x-field>
            <x-field label="Nama" name="name" required>
                <input id="name" name="name" value="{{ old('name', $type->name) }}" required maxlength="100" class="input @error('name') input-error @enderror" placeholder="LPG 3 kg">
            </x-field>
            <x-field label="Berat (kg)" name="weight_kg" required>
                <input id="weight_kg" name="weight_kg" type="number" step="0.1" min="0.1" value="{{ old('weight_kg', $type->weight_kg) }}" required class="input num @error('weight_kg') input-error @enderror">
            </x-field>
            <x-field label="Stok minimum (tabung isi)" name="min_stock" required hint="Dashboard memberi tanda merah bila stok isi di bawah angka ini.">
                <input id="min_stock" name="min_stock" type="number" min="0" value="{{ old('min_stock', $type->min_stock ?? 0) }}" required class="input num @error('min_stock') input-error @enderror">
            </x-field>
            <div class="sm:col-span-2"><x-active-toggle :checked="$type->is_active" /></div>
        </div>

        @unless ($editing)
            <p class="mt-5 rounded-field bg-paper px-3 py-2 text-xs text-muted">Saldo awal jenis baru = 0. Isi saldo awal lewat <b>Stok Opname</b> atau <b>Penyesuaian → Koreksi</b>.</p>
        @endunless

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('master.cylinder-types.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button>
        </div>
    </form>
</x-layouts.app>
