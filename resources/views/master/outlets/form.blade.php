@php $editing = $outlet->exists; @endphp
<x-layouts.app :title="$editing ? 'Ubah Pangkalan' : 'Tambah Pangkalan'">
    <x-page-header :title="$editing ? 'Ubah Pangkalan' : 'Tambah Pangkalan'" :back="$editing ? route('master.outlets.show', $outlet) : route('master.outlets.index')" />

    <form method="POST" action="{{ $editing ? route('master.outlets.update', $outlet) : route('master.outlets.store') }}" class="grid gap-6 lg:grid-cols-[1fr_340px]">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card p-6">
            <h2 class="font-bold">Data Pangkalan</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-field label="Kode" name="code" required>
                    <input id="code" name="code" value="{{ old('code', $outlet->code) }}" required maxlength="20" class="input uppercase @error('code') input-error @enderror" placeholder="PKL001">
                </x-field>
                <x-field label="No. HP" name="phone">
                    <input id="phone" name="phone" value="{{ old('phone', $outlet->phone) }}" maxlength="30" class="input @error('phone') input-error @enderror">
                </x-field>
                <x-field label="Nama pangkalan" name="name" required class="sm:col-span-2">
                    <input id="name" name="name" value="{{ old('name', $outlet->name) }}" required maxlength="150" class="input @error('name') input-error @enderror">
                </x-field>
                <x-field label="Nama pemilik" name="owner_name" class="sm:col-span-2">
                    <input id="owner_name" name="owner_name" value="{{ old('owner_name', $outlet->owner_name) }}" maxlength="100" class="input @error('owner_name') input-error @enderror">
                </x-field>
                <x-field label="Alamat" name="address" class="sm:col-span-2">
                    <textarea id="address" name="address" rows="3" class="input @error('address') input-error @enderror">{{ old('address', $outlet->address) }}</textarea>
                </x-field>
                <div class="sm:col-span-2"><x-active-toggle :checked="$outlet->is_active" /></div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="font-bold">Kuota Bulanan</h2>
                <p class="mt-1 text-xs text-muted">Jumlah tabung isi per bulan. Kosongkan bila pangkalan tidak menerima jenis tersebut.</p>
                <div class="mt-5 space-y-3">
                    @foreach ($types as $type)
                        <div class="flex items-center gap-3">
                            <label for="quota-{{ $type->id }}" class="flex-1 text-sm font-medium">{{ $type->name }}</label>
                            <input id="quota-{{ $type->id }}" name="quotas[{{ $type->id }}]" type="number" min="0" placeholder="0"
                                value="{{ old('quotas.'.$type->id, $quotas[$type->id] ?? '') }}"
                                class="input num w-28 text-right @error('quotas.'.$type->id) input-error @enderror">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('master.outlets.index') }}" class="btn btn-secondary">Batal</a>
                <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button>
            </div>
        </div>
    </form>
</x-layouts.app>
