@php
    $rows = $types->values()->map(fn ($t, $i) => [
        'cylinder_type_id' => $t->id,
        'name' => $t->name,
        'stock_full' => $t->stockOf('isi'),
        'stock_empty' => $t->stockOf('kosong'),
        'qty_full_in' => old("items.$i.qty_full_in", ''),
        'qty_empty_out' => old("items.$i.qty_empty_out", ''),
    ]);
@endphp
<x-layouts.app title="Penerimaan Baru">
    <x-page-header title="Penerimaan Baru" :back="route('receipts.index')">
        <x-slot:actions>
            <div class="text-right text-sm">
                <div class="text-xs text-muted">No. penerimaan (otomatis)</div>
                <div class="doc-number text-base">{{ $nextNumber }}</div>
            </div>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('receipts.store') }}" x-data="receiptForm({ rows: @js($rows) })" class="space-y-6">
        @csrf
        <div class="card grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">
            <x-field label="Tanggal" name="date" required>
                <input id="date" name="date" type="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="input @error('date') input-error @enderror">
            </x-field>
            <x-field label="SPBE" name="supplier_id" required>
                <select id="supplier_id" name="supplier_id" required class="input @error('supplier_id') input-error @enderror">
                    <option value="">Pilih SPBE…</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field label="No. LO / DO" name="ref_document">
                <input id="ref_document" name="ref_document" value="{{ old('ref_document') }}" maxlength="50" class="input num @error('ref_document') input-error @enderror" placeholder="Nomor dokumen SPBE">
            </x-field>
            <x-field label="Kendaraan" name="vehicle_id">
                <select id="vehicle_id" name="vehicle_id" class="input @error('vehicle_id') input-error @enderror">
                    <option value="">—</option>
                    @foreach ($vehicles as $v)
                        <option value="{{ $v->id }}" @selected(old('vehicle_id') == $v->id)>{{ $v->label() }}</option>
                    @endforeach
                </select>
            </x-field>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-line px-6 py-4">
                <h2 class="font-bold">Item Tabung</h2>
                <p class="text-xs text-muted">Kosong dikirim otomatis mengikuti isi diterima (tukar 1:1), tetap bisa diubah.</p>
                @error('items')<p class="mt-2 text-sm font-medium text-flame">{{ $message }}</p>@enderror
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th class="text-right">Stok kosong</th>
                            <th class="w-40 text-right">Isi diterima</th>
                            <th class="w-40 text-right">Kosong dikirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in rows" :key="row.cylinder_type_id">
                            <tr>
                                <td class="font-medium">
                                    <span x-text="row.name"></span>
                                    <input type="hidden" :name="`items[${i}][cylinder_type_id]`" :value="row.cylinder_type_id">
                                </td>
                                <td class="num text-right text-muted" x-text="fmt(row.stock_empty)"></td>
                                <td>
                                    <input type="number" min="0" inputmode="numeric" :name="`items[${i}][qty_full_in]`" x-model="row.qty_full_in" @input="onFullInput(row)"
                                        class="input num text-right" placeholder="0">
                                </td>
                                <td>
                                    <input type="number" min="0" inputmode="numeric" :name="`items[${i}][qty_empty_out]`" x-model="row.qty_empty_out" @input="onEmptyInput(row)"
                                        class="input num text-right" :class="exceeds(row) && 'input-error'" placeholder="0">
                                    <p x-show="exceeds(row)" x-cloak class="mt-1 text-right text-xs font-medium text-flame">Melebihi stok kosong</p>
                                </td>
                            </tr>
                        </template>
                        @foreach ($rows as $i => $row)
                            @foreach (['qty_full_in', 'qty_empty_out'] as $f)
                                @error("items.$i.$f")
                                    <tr><td colspan="4" class="bg-flame/5 text-sm font-medium text-flame">{{ $message }}</td></tr>
                                @enderror
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-paper/60 font-semibold">
                            <td class="px-4 py-3" colspan="2">Total</td>
                            <td class="num px-4 py-3 text-right text-gas" x-text="'+' + fmt(totalFull)"></td>
                            <td class="num px-4 py-3 text-right" x-text="'−' + fmt(totalEmpty)"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card p-6">
            <x-field label="Catatan" name="note">
                <textarea id="note" name="note" rows="2" class="input" maxlength="1000">{{ old('note') }}</textarea>
            </x-field>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('receipts.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="hasError"><x-icon name="save" class="size-4" /> Simpan Penerimaan</button>
        </div>
    </form>
</x-layouts.app>
