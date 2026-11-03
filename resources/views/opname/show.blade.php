@php
    $editable = $opname->isDraft() && auth()->user()->can('opname.create');
    $conditions = \App\Enums\StockCondition::cases();
@endphp
<x-layouts.app :title="$opname->number">
    <x-page-header :title="$opname->number" :subtitle="'Stok opname · '.$opname->date->translatedFormat('d F Y')" :back="route('stock-opnames.index')">
        <x-slot:actions>
            @if ($opname->isSubmitted())
                @can('opname.approve')
                    <x-reason-modal :action="route('stock-opnames.reject', $opname)" title="Tolak hasil opname?" button="Tolak" confirm="Tolak" label="Alasan penolakan" icon="x" />
                    <form method="POST" action="{{ route('stock-opnames.approve', $opname) }}" onsubmit="return confirm('Setujui opname? Selisih akan dibukukan dan stok sistem disesuaikan.')">
                        @csrf
                        <button class="btn btn-primary"><x-icon name="check" class="size-4" /> Setujui</button>
                    </form>
                @endcan
            @endif
            @unless ($opname->isDraft())
                <a href="{{ route('stock-opnames.pdf', $opname) }}" target="_blank" class="btn btn-secondary"><x-icon name="download" class="size-4" /> PDF</a>
            @endunless
            @if ($editable)
                <form method="POST" action="{{ route('stock-opnames.destroy', $opname) }}" onsubmit="return confirm('Hapus draft opname ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-secondary text-flame"><x-icon name="trash" class="size-4" /> Hapus Draft</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <div class="card p-4"><div class="text-xs text-muted">Status</div><div class="mt-1"><x-status :value="$opname->status" /></div></div>
        <div class="card p-4"><div class="text-xs text-muted">Petugas</div><div class="mt-1 font-semibold">{{ $opname->user->name }}</div></div>
        <div class="card p-4"><div class="text-xs text-muted">Diajukan</div><div class="mt-1 font-semibold">{{ $opname->submitted_at?->translatedFormat('d M Y H:i') ?? '—' }}</div></div>
        <div class="card p-4"><div class="text-xs text-muted">{{ $opname->status === \App\Enums\OpnameStatus::Rejected ? 'Ditolak' : 'Disetujui' }}</div>
            <div class="mt-1 font-semibold">{{ $opname->approver ? $opname->approver->name.' · '.$opname->approved_at->translatedFormat('d M Y') : '—' }}</div></div>
    </div>

    @if ($opname->review_note)
        <div class="mb-6 rounded-card border border-line bg-surface px-4 py-3 text-sm"><span class="text-muted">Catatan peninjau:</span> {{ $opname->review_note }}</div>
    @endif
    @if ($opname->adjustment)
        <div class="mb-6 flex items-center gap-3 rounded-card border border-gas/30 bg-gas/5 px-4 py-3 text-sm">
            <x-icon name="check-circle" class="size-5 text-gas" />
            <div>Selisih dibukukan sebagai penyesuaian <a href="{{ route('adjustments.show', $opname->adjustment) }}" class="doc-number underline hover:text-gas">{{ $opname->adjustment->number }}</a> dan tercatat di kartu stok.</div>
        </div>
    @endif

    @if ($editable)
        <div class="mb-4 rounded-card border border-line bg-surface px-4 py-3 text-sm text-muted">
            Hitung fisik setiap jenis dan kondisi tabung, lalu isi kolom <b class="text-ink">Fisik</b>. Klik <b class="text-ink">Ajukan</b> setelah selesai menghitung.
            Stok sistem dipotret saat diajukan, jadi transaksi berikutnya tidak mengacaukan selisih.
        </div>
    @endif

    <form method="POST" action="{{ route('stock-opnames.update', $opname) }}">
        @csrf @method('PUT')
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($items as $typeId => $rows)
                <div class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-3 font-bold">{{ $rows->first()->cylinderType->name }}</div>
                    <table class="table">
                        <thead><tr><th>Kondisi</th><th class="text-right">Sistem</th><th class="w-32 text-right">Fisik</th><th class="text-right">Selisih</th></tr></thead>
                        <tbody>
                            @foreach ($conditions as $condition)
                                @php $item = $rows->firstWhere('condition', $condition); @endphp
                                @continue(! $item)
                                @php $system = $opname->isDraft() ? ($balances[$typeId][$condition->value] ?? 0) : $item->system_qty; @endphp
                                <tr x-data="{ sys: {{ $system }}, phy: @js(old('physical.'.$item->id, $item->physical_qty)) }">
                                    <td><x-status :value="$condition" /></td>
                                    <td class="num text-right">@num($system)</td>
                                    <td>
                                        @if ($editable)
                                            <input type="number" min="0" inputmode="numeric" name="physical[{{ $item->id }}]" x-model="phy" placeholder="—"
                                                class="input num text-right @error('physical.'.$item->id) input-error @enderror">
                                        @else
                                            <div class="num text-right font-semibold">{{ $item->physical_qty !== null ? number_format($item->physical_qty, 0, ',', '.') : '—' }}</div>
                                        @endif
                                    </td>
                                    <td class="num text-right font-semibold">
                                        @if ($editable)
                                            <span x-show="phy !== null && phy !== ''" :class="phy - sys < 0 ? 'text-flame' : (phy - sys > 0 ? 'text-gas' : 'text-muted')"
                                                x-text="(phy - sys > 0 ? '+' : '') + new Intl.NumberFormat('id-ID').format(phy - sys)"></span>
                                        @elseif (! $opname->isDraft())
                                            <span @class(['text-flame' => $item->difference < 0, 'text-gas' => $item->difference > 0, 'text-muted' => $item->difference === 0])>{{ $item->difference > 0 ? '+' : '' }}@num($item->difference)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

        <div class="card mt-6 p-5">
            <x-field label="Catatan" name="note">
                @if ($editable)
                    <textarea id="note" name="note" rows="2" class="input">{{ old('note', $opname->note) }}</textarea>
                @else
                    <p class="text-sm">{{ $opname->note ?: '—' }}</p>
                @endif
            </x-field>
        </div>

        @if ($editable)
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="submit" name="action" value="draft" class="btn btn-secondary" formnovalidate><x-icon name="save" class="size-4" /> Simpan Draft</button>
                <button type="submit" name="action" value="submit" class="btn btn-primary" onclick="return confirm('Ajukan hasil hitung? Setelah diajukan, angka tidak bisa diubah.')"><x-icon name="send" class="size-4" /> Ajukan untuk Persetujuan</button>
            </div>
        @endif
    </form>
</x-layouts.app>
