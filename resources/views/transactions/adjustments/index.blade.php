<x-layouts.app title="Penyesuaian Stok">
    <x-page-header title="Penyesuaian Stok" subtitle="Tabung rusak/bocor, hilang, koreksi, dan hasil stok opname. Stok berubah setelah disetujui.">
        <x-slot:actions>
            @can('adjustments.create')
                <a href="{{ route('adjustments.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Penyesuaian Baru</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid gap-3 border-b border-line p-4 sm:grid-cols-[1fr_200px_200px_auto]">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="No. dokumen / alasan…" class="input">
            <select name="type" class="input">
                <option value="">Semua jenis</option>
                @foreach (\App\Enums\AdjustmentType::cases() as $t)
                    <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">Semua status</option>
                @foreach (\App\Enums\ApprovalStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary"><x-icon name="filter" class="size-4" /> Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Dokumen</th><th>Tanggal</th><th>Jenis</th><th>Item</th><th class="hidden md:table-cell">Alasan</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($adjustments as $a)
                        <tr>
                            <td><a href="{{ route('adjustments.show', $a) }}" class="doc-number hover:text-gas">{{ $a->number }}</a></td>
                            <td class="whitespace-nowrap">{{ $a->date->translatedFormat('d M Y') }}</td>
                            <td class="whitespace-nowrap">{{ $a->type->label() }}</td>
                            <td class="text-xs">
                                @foreach ($a->items->take(3) as $item)
                                    <div class="whitespace-nowrap"><span class="text-muted">{{ $item->cylinderType->code }}</span> {{ $item->directionLabel() }} <b class="num">@num($item->qty)</b></div>
                                @endforeach
                                @if ($a->items->count() > 3)<div class="text-muted">+{{ $a->items->count() - 3 }} lainnya</div>@endif
                            </td>
                            <td class="hidden max-w-xs truncate text-muted md:table-cell">{{ $a->reason }}</td>
                            <td><x-status :value="$a->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="adjustment" title="Belum ada penyesuaian" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($adjustments->hasPages())<div class="border-t border-line p-4">{{ $adjustments->links() }}</div>@endif
    </div>
</x-layouts.app>
