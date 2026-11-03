<x-layouts.app title="Jenis Tabung">
    <x-page-header title="Jenis Tabung" subtitle="Ukuran tabung yang ditangani beserta batas stok minimum tabung isi.">
        <x-slot:actions>
            @can('master.manage')
                <a href="{{ route('master.cylinder-types.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Jenis</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th class="text-right">Berat</th>
                    <th class="text-right">Stok Min.</th>
                    <th class="text-right">Isi</th>
                    <th class="text-right">Kosong</th>
                    <th class="text-right">Rusak</th>
                    <th>Status</th>
                    @can('master.manage')<th></th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse ($types as $type)
                    <tr>
                        <td class="doc-number">{{ $type->code }}</td>
                        <td class="font-medium">{{ $type->name }}</td>
                        <td class="num text-right">{{ $type->weightLabel() }}</td>
                        <td class="num text-right">@num($type->min_stock)</td>
                        <td @class(['num text-right font-semibold', 'text-flame' => $type->isBelowMinimum(), 'text-gas' => ! $type->isBelowMinimum()])>@num($type->stockOf('isi'))</td>
                        <td class="num text-right text-muted">@num($type->stockOf('kosong'))</td>
                        <td class="num text-right text-muted">@num($type->stockOf('rusak'))</td>
                        <td>
                            @if ($type->is_active)<x-badge color="gas">Aktif</x-badge>@else<x-badge>Nonaktif</x-badge>@endif
                        </td>
                        @can('master.manage')
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('master.cylinder-types.edit', $type) }}" class="btn-ghost btn btn-sm" title="Ubah"><x-icon name="pencil" class="size-4" /></a>
                                <x-delete-button :action="route('master.cylinder-types.destroy', $type)" confirm="Hapus jenis tabung {{ $type->name }}?" />
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty icon="cylinder" title="Belum ada jenis tabung" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
