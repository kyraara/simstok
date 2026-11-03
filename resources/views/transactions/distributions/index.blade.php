<x-layouts.app title="Distribusi">
    <x-page-header title="Distribusi ke Pangkalan" subtitle="Tabung isi keluar ke pangkalan, sekaligus tabung kosong yang kembali.">
        <x-slot:actions>
            @can('transactions.create')
                <a href="{{ route('distributions.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Distribusi Baru</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-[1fr_220px_140px_140px_130px_auto]">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="No. surat jalan…" class="input">
            <select name="outlet_id" class="input">
                <option value="">Semua pangkalan</option>
                @foreach ($outlets as $o)
                    <option value="{{ $o->id }}" @selected(request('outlet_id') == $o->id)>{{ $o->name }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="input" title="Dari tanggal">
            <input type="date" name="to" value="{{ request('to') }}" class="input" title="Sampai tanggal">
            <select name="status" class="input">
                <option value="">Semua status</option>
                <option value="posted" @selected(request('status') === 'posted')>Tercatat</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Batal</option>
            </select>
            <button class="btn btn-secondary"><x-icon name="filter" class="size-4" /> Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>No. Surat Jalan</th>
                        <th>Tanggal</th>
                        <th>Pangkalan</th>
                        <th class="hidden lg:table-cell">Item</th>
                        <th class="text-right">Isi keluar</th>
                        <th class="text-right">Kosong masuk</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($distributions as $d)
                        <tr @class(['opacity-55' => ! $d->isPosted()])>
                            <td><a href="{{ route('distributions.show', $d) }}" class="doc-number hover:text-gas">{{ $d->number }}</a></td>
                            <td class="whitespace-nowrap">{{ $d->date->translatedFormat('d M Y') }}</td>
                            <td>{{ $d->outlet->name }}</td>
                            <td class="hidden text-xs lg:table-cell">
                                @foreach ($d->items as $item)
                                    <span class="mr-2 whitespace-nowrap text-muted">{{ $item->cylinderType->code }} <b class="num text-ink">@num($item->qty_full_out)</b></span>
                                @endforeach
                            </td>
                            <td class="num text-right font-semibold">−@num($d->items->sum('qty_full_out'))</td>
                            <td class="num text-right text-muted">+@num($d->items->sum('qty_empty_in'))</td>
                            <td><x-status :value="$d->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('distributions.print', $d) }}" target="_blank" class="btn-ghost btn btn-sm" title="Cetak surat jalan"><x-icon name="printer" class="size-4" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty icon="distribution" title="Belum ada distribusi">Distribusi ke pangkalan akan muncul di sini.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($distributions->hasPages())<div class="border-t border-line p-4">{{ $distributions->links() }}</div>@endif
    </div>
</x-layouts.app>
