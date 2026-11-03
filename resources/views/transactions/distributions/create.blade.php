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
            {{-- Baris item: tabel di layar lebar, kartu di HP --}}
            <div class="hidden grid-cols-[1fr_110px_150px_150px_150px] gap-4 border-b border-line bg-paper/60 px-6 py-2.5 text-xs font-semibold tracking-wide text-muted uppercase md:grid">
                <div>Jenis</div><div class="text-right">Stok isi</div><div class="text-right">Isi dikirim</div><div class="text-right">Kosong diterima</div><div class="text-right">Sisa kuota bulan ini</div>
            </div>
            <div class="divide-y divide-line">
                <template x-for="(row, i) in rows" :key="row.cylinder_type_id">
                    <div class="grid grid-cols-2 items-start gap-x-3 gap-y-2 px-4 py-4 md:grid-cols-[1fr_110px_150px_150px_150px] md:gap-4 md:px-6 md:py-3">
                        <div class="font-semibold md:self-center md:font-medium">
                            <span x-text="row.name"></span>
                            <input type="hidden" :name="`items[${i}][cylinder_type_id]`" :value="row.cylinder_type_id">
                        </div>
                        <div class="text-right md:self-center">
                            <span class="text-xs text-muted md:hidden">Stok isi </span>
                            <span class="num font-semibold" :class="row.stock_full > 0 ? 'text-gas' : 'text-flame'" x-text="fmt(row.stock_full)"></span>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-muted md:hidden" :for="`full-${i}`">Isi dikirim</label>
                            <input type="number" min="0" inputmode="numeric" :id="`full-${i}`" :name="`items[${i}][qty_full_out]`" x-model="row.qty_full_out" @input="onFullInput(row)"
                                class="input num text-right" :class="exceeds(row) && 'input-error'" placeholder="0">
                            <p x-show="exceeds(row)" x-cloak class="mt-1 text-right text-xs font-medium text-flame">Stok tidak cukup</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-muted md:hidden" :for="`empty-${i}`">Kosong diterima</label>
                            <div class="relative">
                                <input type="number" min="0" inputmode="numeric" :id="`empty-${i}`" :name="`items[${i}][qty_empty_in]`" x-model="row.qty_empty_in" @input="onEmptyInput(row)"
                                    class="input num pl-8 text-right" placeholder="0">
                                <button type="button" @click="row.linked = !row.linked; onFullInput(row)" tabindex="-1"
                                    class="absolute inset-y-0 left-0 flex items-center px-2" :class="row.linked ? 'text-gas' : 'text-steel/60'"
                                    :title="row.linked ? 'Tukar 1:1 aktif' : 'Klik untuk samakan dengan isi dikirim'">
                                    <x-icon name="link" class="size-3.5" x-show="row.linked" />
                                    <x-icon name="unlink" class="size-3.5" x-show="!row.linked" />
                                </button>
                            </div>
                        </div>
                        <div class="col-span-2 text-right text-sm md:col-span-1 md:self-center">
                            <span class="text-xs text-muted md:hidden">Sisa kuota bulan ini: </span>
                            <template x-if="quotaFor(row)">
                                <span class="num" :class="quotaRemaining(row) < 0 ? 'font-semibold text-flame' : 'text-muted'">
                                    <span x-text="fmt(quotaRemaining(row))"></span> / <span x-text="fmt(quotaFor(row).quota)"></span>
                                </span>
                            </template>
                            <template x-if="!quotaFor(row)"><span class="text-steel">—</span></template>
                        </div>
                    </div>
                </template>
            </div>
            @foreach ($rows as $i => $row)
                @foreach (['qty_full_out', 'qty_empty_in'] as $f)
                    @error("items.$i.$f")
                        <p class="border-t border-line bg-flame/5 px-6 py-2 text-sm font-medium text-flame">{{ $message }}</p>
                    @enderror
                @endforeach
            @endforeach
            <div class="flex justify-between gap-4 border-t border-line bg-paper/60 px-4 py-3 text-sm font-semibold md:grid md:grid-cols-[1fr_110px_150px_150px_150px] md:px-6">
                <div class="md:col-span-2">Total</div>
                <div class="num text-right"><span class="font-normal text-muted md:hidden">isi </span><span x-text="fmt(totalFull)"></span></div>
                <div class="num text-right"><span class="font-normal text-muted md:hidden">kosong </span><span x-text="fmt(totalEmpty)"></span></div>
                <div class="hidden md:block"></div>
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
