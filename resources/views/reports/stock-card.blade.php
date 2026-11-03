<x-layouts.app title="Kartu Stok">
    <x-page-header title="Kartu Stok" subtitle="Riwayat mutasi per jenis tabung dan kondisi. Setiap baris berasal dari buku besar mutasi.">
        <x-slot:actions><x-export-buttons /></x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_160px_160px_auto] lg:items-end">
        <input type="hidden" name="condition" value="{{ $condition->value }}">
        <div>
            <label class="label" for="cylinder_type_id">Jenis tabung</label>
            <select id="cylinder_type_id" name="cylinder_type_id" class="input">
                @foreach ($types as $t)
                    <option value="{{ $t->id }}" @selected($t->id === $type->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="label" for="from">Dari</label><input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
        <div><label class="label" for="to">Sampai</label><input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
        <button class="btn btn-primary"><x-icon name="filter" class="size-4" /> Tampilkan</button>
    </form>

    {{-- Pilihan kondisi --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (\App\Enums\StockCondition::cases() as $c)
            @php $active = $c === $condition; @endphp
            <a href="{{ request()->fullUrlWithQuery(['condition' => $c->value, 'export' => null]) }}" @class([
                'flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-semibold transition',
                'border-ink bg-ink text-white' => $active,
                'border-line bg-surface text-muted hover:text-ink' => ! $active,
            ])>
                <span @class(['size-2 rounded-full', 'bg-gas' => $c->value === 'isi', 'bg-steel' => $c->value === 'kosong', 'bg-flame' => $c->value === 'rusak'])></span>
                {{ $c->label() }}
                <span class="num {{ $active ? 'text-white/70' : 'text-steel' }}">@num($type->stockOf($c))</span>
            </a>
        @endforeach
    </div>

    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([['Saldo awal', $card['opening'], ''], ['Masuk', $card['in'], 'text-gas'], ['Keluar', $card['out'], 'text-flame'], ['Saldo akhir', $card['closing'], '']] as [$label, $value, $color])
            <div class="card p-4">
                <div class="text-xs text-muted">{{ $label }}</div>
                <div class="num mt-1 text-xl font-semibold {{ $color }}">@num($value)</div>
            </div>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead>
                <tr><th>Waktu</th><th>No. Dokumen</th><th>Keterangan</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Saldo</th><th class="hidden md:table-cell">Petugas</th></tr>
            </thead>
            <tbody>
                <tr class="bg-paper/40">
                    <td class="text-sm text-muted">{{ $from->translatedFormat('d M Y') }}</td>
                    <td></td>
                    <td class="font-medium">Saldo awal</td>
                    <td></td><td></td>
                    <td class="num text-right font-semibold">@num($card['opening'])</td>
                    <td class="hidden md:table-cell"></td>
                </tr>
                @forelse ($card['movements'] as $m)
                    @php
                        $ref = $m->reference;
                        $url = match ($m->reference_type) {
                            'receipt' => route('receipts.show', $m->reference_id),
                            'distribution' => route('distributions.show', $m->reference_id),
                            'adjustment' => route('adjustments.show', $m->reference_id),
                            default => null,
                        };
                    @endphp
                    <tr>
                        <td class="text-sm whitespace-nowrap">{{ $m->moved_at->translatedFormat('d M Y') }}<div class="text-xs text-muted">{{ $m->moved_at->format('H:i') }}</div></td>
                        <td>@if ($url)<a href="{{ $url }}" class="doc-number hover:text-gas">{{ $ref?->number }}</a>@else<span class="doc-number">{{ $ref?->number ?? '—' }}</span>@endif</td>
                        <td class="text-sm">
                            <span @class(['font-medium', 'text-flame' => $m->type === \App\Enums\MovementType::Pembatalan])>{{ $m->type->label() }}</span>
                            @if ($m->reference_type === 'distribution' && $ref?->outlet_id)
                                <span class="text-muted">· {{ $ref->outlet?->name }}</span>
                            @elseif ($m->reference_type === 'receipt' && $ref?->supplier_id)
                                <span class="text-muted">· {{ $ref->supplier?->name }}</span>
                            @endif
                            @if ($m->note)<div class="max-w-md truncate text-xs text-muted">{{ $m->note }}</div>@endif
                        </td>
                        <td class="num text-right font-semibold text-gas">{{ $m->qty > 0 ? number_format($m->qty, 0, ',', '.') : '' }}</td>
                        <td class="num text-right font-semibold text-flame">{{ $m->qty < 0 ? number_format(-$m->qty, 0, ',', '.') : '' }}</td>
                        <td class="num text-right font-semibold">@num($m->running_balance)</td>
                        <td class="hidden text-sm text-muted md:table-cell">{{ $m->user?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="stock-card" title="Tidak ada mutasi pada periode ini" /></td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-paper/70 font-bold">
                    <td class="px-4 py-3" colspan="3">Saldo akhir {{ $to->translatedFormat('d M Y') }}</td>
                    <td class="num px-4 py-3 text-right text-gas">@num($card['in'])</td>
                    <td class="num px-4 py-3 text-right text-flame">@num($card['out'])</td>
                    <td class="num px-4 py-3 text-right">@num($card['closing'])</td>
                    <td class="hidden md:table-cell"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-layouts.app>
