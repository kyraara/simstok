@php $editing = $supplier->exists; @endphp
<x-layouts.app :title="$editing ? 'Ubah SPBE' : 'Tambah SPBE'">
    <x-page-header :title="$editing ? 'Ubah SPBE' : 'Tambah SPBE'" :back="route('master.suppliers.index')" />

    <form method="POST" action="{{ $editing ? route('master.suppliers.update', $supplier) : route('master.suppliers.store') }}" class="card max-w-2xl p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Kode" name="code" required>
                <input id="code" name="code" value="{{ old('code', $supplier->code) }}" required maxlength="20" class="input uppercase @error('code') input-error @enderror" placeholder="SPBE01">
            </x-field>
            <x-field label="No. telepon" name="phone">
                <input id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}" maxlength="30" class="input @error('phone') input-error @enderror">
            </x-field>
            <x-field label="Nama SPBE" name="name" required class="sm:col-span-2">
                <input id="name" name="name" value="{{ old('name', $supplier->name) }}" required maxlength="150" class="input @error('name') input-error @enderror">
            </x-field>
            <x-field label="Alamat" name="address" class="sm:col-span-2">
                <textarea id="address" name="address" rows="3" class="input @error('address') input-error @enderror">{{ old('address', $supplier->address) }}</textarea>
            </x-field>
            <div class="sm:col-span-2"><x-active-toggle :checked="$supplier->is_active" /></div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('master.suppliers.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button>
        </div>
    </form>
</x-layouts.app>
