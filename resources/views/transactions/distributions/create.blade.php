@php
    $rows = $types->values()->map(fn ($t, $i) => [
        'cylinder_type_id' => $t->id,
        'name' => $t->name,
        'stock_full' => $t->stockOf('isi'),
        'qty_full_out' => old("items.$i.qty_full_out", ''),
        'qty_empty_in' => old("items.$i.qty_empty_in", ''),
    ]);
    $outletOptions = $outlets->map(fn ($o) => ['id' => $o->id, 'label' => $o->name, 'sub' => $o->code.($o->owner_name ? ' · '.$o->owner_name : '')])->values();
@endphp
<x-layouts.app title="Distribusi Baru">
    <x-page-header title="Distribusi Baru" :back="route('distributions.index')">
        <x-slot:actions>
            <div class="text-right text-sm">
                <div class="text-xs text-muted">No. surat jalan (otomatis)</div>
                <div class="doc-number text-base">{{ $nextNumber }}</div>
            </div>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('distributions.store') }}" class="space-y-6"
        x-data="distributionForm({ rows: @js($rows), quotaUrl: @js(route('distributions.quota')), outletId: @js(old('outlet_id') ? (int) old('outlet_id') : null), date: @js(old('date', now()->toDateString())) })">
        @csrf
        <div class="card grid gap-5 p-6 sm:grid-cols-[180px_1fr_1fr]">
            <x-field label="Tanggal" name="date" required>
                <input id="date" name="date" type="date" x-model="date" max="{{ now()->toDateString() }}" required class="input @error('date') input-error @enderror">
            </x-field>
            <x-field label="Pangkalan" name="outlet_id" required>
                <x-combobox name="outlet_id" :options="$outletOptions" :selected="old('outlet_id')" placeholder="Cari pangkalan…" model="outletId" :invalid="$errors->has('outlet_id')" />
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
            <div class="flex flex-wrap items-end justify-between gap-2 border-b border-line px-6 py-4">
                <div>
                    <h2 class="font-bold">Item Tabung</h2>
                    <p class="text-xs text-muted">Kosong diterima otomatis mengikuti isi dikirim (tukar 1:1), tetap bisa diubah.</p>
                </div>
                @error('items')<p class="text-sm font-medium text-flame">{{ $message }}</p>@enderror
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th class="text-right">Stok isi</th>
                            <th class="w-36 text-right">Isi dikirim</th>
                            <th class="w-36 text-right">Kosong diterima</th>
                            <th class="text-right">Sisa kuota bulan ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in rows" :key="row.cylinder_type_id">
                            <tr>
                                <td class="font-medium">
                                    <span x-text="row.name"></span>
                                    <input type="hidden" :name="`items[${i}][cylinder_type_id]`" :value="row.cylinder_type_id">
                                </td>
                                <td class="num text-right font-semibold" :class="row.stock_full > 0 ? 'text-gas' : 'text-flame'" x-text="fmt(row.stock_full)"></td>
                                <td>
                                    <input type="number" min="0" inputmode="numeric" :name="`items[${i}][qty_full_out]`" x-model="row.qty_full_out" @input="onFullInput(row)"
                                        class="input num text-right" :class="exceeds(row) && 'input-error'" placeholder="0">
                                    <p x-show="exceeds(row)" x-cloak class="mt-1 text-right text-xs font-medium text-flame">Stok tidak cukup</p>
                                </td>
                                <td>
                                    <div class="relative">
                                        <input type="number" min="0" inputmode="numeric" :name="`items[${i}][qty_empty_in]`" x-model="row.qty_empty_in" @input="onEmptyInput(row)"
                                            class="input num pl-8 text-right" placeholder="0">
                                        <button type="button" @click="row.linked = !row.linked; onFullInput(row)" tabindex="-1"
                                            class="absolute inset-y-0 left-0 flex items-center px-2" :class="row.linked ? 'text-gas' : 'text-steel/60'"
                                            :title="row.linked ? 'Tukar 1:1 aktif' : 'Klik untuk samakan dengan isi dikirim'">
                                            <x-icon name="link" class="size-3.5" x-show="row.linked" />
                                            <x-icon name="unlink" class="size-3.5" x-show="!row.linked" />
                                        </button>
                                    </div>
                                </td>
                                <td class="num text-right text-sm whitespace-nowrap">
                                    <template x-if="quotaFor(row)">
                                        <span :class="quotaRemaining(row) < 0 ? 'font-semibold text-flame' : 'text-muted'">
                                            <span x-text="fmt(quotaRemaining(row))"></span> / <span x-text="fmt(quotaFor(row).quota)"></span>
                                        </span>
                                    </template>
                                    <template x-if="!quotaFor(row)"><span class="text-steel">—</span></template>
                                </td>
                            </tr>
                        </template>
                        @foreach ($rows as $i => $row)
                            @foreach (['qty_full_out', 'qty_empty_in'] as $f)
                                @error("items.$i.$f")
                                    <tr><td colspan="5" class="bg-flame/5 text-sm font-medium text-flame">{{ $message }}</td></tr>
                                @enderror
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-paper/60 font-semibold">
                            <td class="px-4 py-3" colspan="2">Total</td>
                            <td class="num px-4 py-3 text-right" x-text="fmt(totalFull)"></td>
                            <td class="num px-4 py-3 text-right" x-text="fmt(totalEmpty)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div x-show="rows.some(r => quotaRemaining(r) !== null && quotaRemaining(r) < 0)" x-cloak
                class="flex items-center gap-2 border-t border-amber/40 bg-amber/10 px-6 py-3 text-sm text-[#8a5413]">
                <x-icon name="alert" class="size-4 shrink-0" /> Jumlah melebihi kuota bulanan pangkalan. Distribusi tetap bisa disimpan.
            </div>
        </div>

        <div class="card p-6">
            <x-field label="Catatan" name="note">
                <textarea id="note" name="note" rows="2" class="input" maxlength="1000">{{ old('note') }}</textarea>
            </x-field>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('distributions.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-secondary" :disabled="hasError"><x-icon name="save" class="size-4" /> Simpan</button>
            <button type="submit" name="print" value="1" class="btn btn-primary" :disabled="hasError"><x-icon name="printer" class="size-4" /> Simpan & Cetak Surat Jalan</button>
        </div>
    </form>
</x-layouts.app>
