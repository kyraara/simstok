<x-layouts.app title="Stok Opname">
    <x-page-header title="Stok Opname" subtitle="Pencocokan stok sistem dengan hitungan fisik di gudang." />

    @can('opname.create')
        @if ($open)
            <div class="mb-6 flex flex-col gap-3 rounded-card border border-amber/40 bg-amber/10 px-5 py-4 sm:flex-row sm:items-center">
                <x-icon name="clock" class="size-5 shrink-0 text-[#a8661a]" />
                <div class="flex-1 text-sm">
                    Sesi <b class="doc-number">{{ $open->number }}</b> masih <b>{{ strtolower($open->status->label()) }}</b>.
                    Selesaikan sesi ini sebelum membuat sesi baru.
                </div>
                <a href="{{ route('stock-opnames.show', $open) }}" class="btn btn-secondary btn-sm">Buka Sesi</a>
            </div>
        @else
            <form method="POST" action="{{ route('stock-opnames.store') }}" class="card mb-6 grid gap-4 p-5 sm:grid-cols-[180px_1fr_auto] sm:items-end">
                @csrf
                <x-field label="Tanggal opname" name="date" required>
                    <input id="date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="input">
                </x-field>
                <x-field label="Catatan" name="note">
                    <input id="note" name="note" value="{{ old('note') }}" class="input" placeholder="Mis. opname akhir bulan">
                </x-field>
                <button class="btn btn-primary"><x-icon name="plus" class="size-4" /> Mulai Sesi Opname</button>
            </form>
        @endif
    @endcan

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Opname</th><th>Tanggal</th><th>Petugas</th><th class="text-right">Item berselisih</th><th class="text-right">Total selisih</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($opnames as $o)
                        @php $diffItems = $o->items->where('difference', '!=', 0); @endphp
                        <tr>
                            <td><a href="{{ route('stock-opnames.show', $o) }}" class="doc-number hover:text-gas">{{ $o->number }}</a></td>
                            <td class="whitespace-nowrap">{{ $o->date->translatedFormat('d M Y') }}</td>
                            <td>{{ $o->user->name }}</td>
                            <td class="num text-right">{{ $o->isDraft() ? '—' : $diffItems->count() }}</td>
                            <td class="num text-right">
                                @if ($o->isDraft())—@else
                                    @php $total = $o->items->sum('difference'); @endphp
                                    <span @class(['text-flame' => $total < 0, 'text-gas' => $total > 0])>{{ $total > 0 ? '+' : '' }}@num($total)</span>
                                @endif
                            </td>
                            <td><x-status :value="$o->status" /></td>
                            <td class="text-right">
                                @unless ($o->isDraft())
                                    <a href="{{ route('stock-opnames.pdf', $o) }}" target="_blank" class="btn-ghost btn btn-sm" title="PDF"><x-icon name="download" class="size-4" /></a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="opname" title="Belum ada stok opname">Mulai sesi opname untuk mencocokkan stok sistem dengan fisik.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($opnames->hasPages())<div class="border-t border-line p-4">{{ $opnames->links() }}</div>@endif
    </div>
</x-layouts.app>
