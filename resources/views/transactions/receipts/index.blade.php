<x-layouts.app title="Penerimaan SPBE">
    <x-page-header title="Penerimaan dari SPBE" subtitle="Tabung isi masuk dari SPBE, sekaligus tabung kosong yang dikirim ke SPBE.">
        <x-slot:actions>
            @can('transactions.create')
                <a href="{{ route('receipts.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Penerimaan Baru</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-[1fr_200px_140px_140px_130px_auto]">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="No. penerimaan / LO…" class="input">
            <select name="supplier_id" class="input">
                <option value="">Semua SPBE</option>
                @foreach ($suppliers as $s)
                    <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
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
                        <th>No. Penerimaan</th>
                        <th>Tanggal</th>
                        <th>SPBE</th>
                        <th class="hidden md:table-cell">No. LO/DO</th>
                        <th class="text-right">Isi masuk</th>
                        <th class="text-right">Kosong keluar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receipts as $r)
                        <tr @class(['opacity-55' => ! $r->isPosted()])>
                            <td><a href="{{ route('receipts.show', $r) }}" class="doc-number hover:text-gas">{{ $r->number }}</a></td>
                            <td class="whitespace-nowrap">{{ $r->date->translatedFormat('d M Y') }}</td>
                            <td>{{ $r->supplier->name }}</td>
                            <td class="num hidden text-sm text-muted md:table-cell">{{ $r->ref_document ?: '—' }}</td>
                            <td class="num text-right font-semibold text-gas">+@num($r->items->sum('qty_full_in'))</td>
                            <td class="num text-right text-muted">−@num($r->items->sum('qty_empty_out'))</td>
                            <td><x-status :value="$r->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="receipt" title="Belum ada penerimaan">Penerimaan dari SPBE akan muncul di sini.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($receipts->hasPages())<div class="border-t border-line p-4">{{ $receipts->links() }}</div>@endif
    </div>
</x-layouts.app>
