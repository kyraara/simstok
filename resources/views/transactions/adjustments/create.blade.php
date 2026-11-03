@php
    $oldRows = collect(old('items', []))->map(fn ($r) => [
        'cylinder_type_id' => isset($r['cylinder_type_id']) ? (int) $r['cylinder_type_id'] : '',
        'condition' => $r['condition'] ?? 'isi',
        'direction' => $r['direction'] ?? 'minus',
        'qty' => $r['qty'] ?? '',
    ])->values();
    $descriptions = [
        'rusak' => 'Tabung isi/kosong yang ditemukan rusak atau bocor dipindah ke stok rusak.',
        'hilang' => 'Tabung hilang mengurangi stok sesuai kondisinya.',
        'koreksi' => 'Menambah atau mengurangi stok karena salah catat. Bisa juga dipakai untuk saldo awal.',
    ];
@endphp
<x-layouts.app title="Penyesuaian Baru">
    <x-page-header title="Penyesuaian Stok Baru" subtitle="Disimpan sebagai pengajuan. Stok baru berubah setelah disetujui Pimpinan." :back="route('adjustments.index')" />

    <form method="POST" action="{{ route('adjustments.store') }}" class="space-y-6"
        x-data="adjustmentForm({ type: @js(old('type', 'rusak')), rows: @js($oldRows), stocks: @js((object) $stocks) })">
        @csrf
        <div class="card p-6">
            <div class="label">Jenis penyesuaian <span class="text-flame">*</span></div>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($adjustmentTypes as $t)
                    <label class="flex cursor-pointer flex-col rounded-field border border-line p-3.5 has-[:checked]:border-gas has-[:checked]:bg-gas/5">
                        <span class="flex items-center gap-2">
                            <input type="radio" name="type" value="{{ $t->value }}" x-model="type" class="text-gas focus:ring-gas/30">
                            <span class="font-semibold">{{ $t->label() }}</span>
                        </span>
                        <span class="mt-1 pl-6 text-xs text-muted">{{ $descriptions[$t->value] }}</span>
                    </label>
                @endforeach
            </div>
            @error('type')<p class="mt-1 text-xs font-medium text-flame">{{ $message }}</p>@enderror

            <div class="mt-5 grid gap-5 sm:grid-cols-[180px_1fr]">
                <x-field label="Tanggal" name="date" required>
                    <input id="date" name="date" type="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="input @error('date') input-error @enderror">
                </x-field>
                <x-field label="Alasan" name="reason" required>
                    <input id="reason" name="reason" value="{{ old('reason') }}" required minlength="5" maxlength="1000" class="input @error('reason') input-error @enderror" placeholder="Mis. ditemukan bocor saat pengecekan pagi">
                </x-field>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-line px-6 py-4">
                <h2 class="font-bold">Item</h2>
                @error('items')<p class="mt-1 text-sm font-medium text-flame">{{ $message }}</p>@enderror
            </div>
            <div class="divide-y divide-line">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="grid items-start gap-3 px-6 py-4 sm:grid-cols-[1fr_160px_160px_130px_auto]">
                        <div>
                            <label class="label sm:hidden">Jenis tabung</label>
                            <select :name="`items[${i}][cylinder_type_id]`" x-model.number="row.cylinder_type_id" required class="input">
                                <option value="">Pilih jenis tabung…</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label sm:hidden" x-text="type === 'rusak' ? 'Kondisi asal' : 'Kondisi'"></label>
                            <select :name="`items[${i}][condition]`" x-model="row.condition" class="input">
                                <template x-for="c in conditions()" :key="c">
                                    <option :value="c" x-text="(type === 'rusak' ? 'Dari ' : '') + c.charAt(0).toUpperCase() + c.slice(1)" :selected="c === row.condition"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <template x-if="type === 'koreksi'">
                                <select :name="`items[${i}][direction]`" x-model="row.direction" class="input">
                                    <option value="plus">Tambah (+)</option>
                                    <option value="minus">Kurangi (−)</option>
                                </select>
                            </template>
                            <template x-if="type === 'rusak'">
                                <div class="flex h-[38px] items-center gap-1.5 text-sm text-muted"><x-icon name="chevron-right" class="size-4" /> ke <x-badge color="flame">Rusak</x-badge></div>
                            </template>
                            <template x-if="type === 'hilang'">
                                <div class="flex h-[38px] items-center text-sm text-muted">Stok berkurang</div>
                            </template>
                        </div>
                        <div>
                            <input type="number" min="1" inputmode="numeric" :name="`items[${i}][qty]`" x-model="row.qty" required placeholder="Jumlah"
                                class="input num text-right" :class="exceeds(row) && 'input-error'">
                            <p class="mt-1 text-right text-xs" :class="exceeds(row) ? 'font-medium text-flame' : 'text-muted'" x-show="row.cylinder_type_id && isOutgoing(row)">
                                Stok: <span class="num" x-text="fmt(available(row))"></span>
                            </p>
                        </div>
                        <button type="button" @click="removeRow(i)" class="btn-ghost btn hover:text-flame" title="Hapus baris"><x-icon name="trash" class="size-4" /></button>
                    </div>
                </template>
            </div>
            @foreach ($errors->get('items.*') as $messages)
                @foreach ($messages as $message)
                    <p class="bg-flame/5 px-6 py-2 text-sm font-medium text-flame">{{ $message }}</p>
                @endforeach
            @endforeach
            <div class="border-t border-line px-6 py-3">
                <button type="button" @click="addRow()" class="btn-ghost btn btn-sm text-gas"><x-icon name="plus" class="size-4" /> Tambah baris</button>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('adjustments.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="hasError"><x-icon name="send" class="size-4" /> Ajukan Penyesuaian</button>
        </div>
    </form>
</x-layouts.app>
