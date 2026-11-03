<x-layouts.app title="Kendaraan">
    <x-page-header title="Kendaraan" subtitle="Armada pengangkut beserta sopirnya.">
        <x-slot:actions>
            @can('master.manage')
                <a href="{{ route('master.vehicles.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Kendaraan</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="border-b border-line p-4"><x-search placeholder="Cari no. polisi atau sopir…" /></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>No. Polisi</th><th>Jenis</th><th>Sopir</th><th>Status</th>@can('master.manage')<th></th>@endcan</tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td class="doc-number">{{ $vehicle->plate_number }}</td>
                            <td>{{ $vehicle->type ?: '—' }}</td>
                            <td>{{ $vehicle->driver_name ?: '—' }}</td>
                            <td>@if ($vehicle->is_active)<x-badge color="gas">Aktif</x-badge>@else<x-badge>Nonaktif</x-badge>@endif</td>
                            @can('master.manage')
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('master.vehicles.edit', $vehicle) }}" class="btn-ghost btn btn-sm" title="Ubah"><x-icon name="pencil" class="size-4" /></a>
                                    <x-delete-button :action="route('master.vehicles.destroy', $vehicle)" confirm="Hapus kendaraan {{ $vehicle->plate_number }}?" />
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="truck" title="Belum ada kendaraan" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vehicles->hasPages())<div class="border-t border-line p-4">{{ $vehicles->links() }}</div>@endif
    </div>
</x-layouts.app>
