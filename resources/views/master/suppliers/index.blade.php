<x-layouts.app title="SPBE / Pemasok">
    <x-page-header title="SPBE / Pemasok" subtitle="Stasiun pengisian asal tabung isi.">
        <x-slot:actions>
            @can('master.manage')
                <a href="{{ route('master.suppliers.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah SPBE</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="border-b border-line p-4"><x-search placeholder="Cari kode atau nama SPBE…" /></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Kode</th><th>Nama</th><th>Alamat</th><th>Telepon</th><th class="text-right">Penerimaan</th><th>Status</th>@can('master.manage')<th></th>@endcan</tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="doc-number">{{ $supplier->code }}</td>
                            <td class="font-medium">{{ $supplier->name }}</td>
                            <td class="max-w-xs truncate text-muted">{{ $supplier->address ?: '—' }}</td>
                            <td class="num whitespace-nowrap">{{ $supplier->phone ?: '—' }}</td>
                            <td class="num text-right">@num($supplier->receipts_count)</td>
                            <td>@if ($supplier->is_active)<x-badge color="gas">Aktif</x-badge>@else<x-badge>Nonaktif</x-badge>@endif</td>
                            @can('master.manage')
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('master.suppliers.edit', $supplier) }}" class="btn-ghost btn btn-sm" title="Ubah"><x-icon name="pencil" class="size-4" /></a>
                                    <x-delete-button :action="route('master.suppliers.destroy', $supplier)" confirm="Hapus SPBE {{ $supplier->name }}?" />
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="factory" title="Belum ada SPBE" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($suppliers->hasPages())<div class="border-t border-line p-4">{{ $suppliers->links() }}</div>@endif
    </div>
</x-layouts.app>
