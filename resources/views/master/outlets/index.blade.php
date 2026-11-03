<x-layouts.app title="Pangkalan">
    <x-page-header title="Pangkalan" subtitle="Tujuan distribusi beserta kuota bulanan per jenis tabung.">
        <x-slot:actions>
            @can('master.manage')
                <a href="{{ route('master.outlets.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Pangkalan</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="border-b border-line p-4"><x-search placeholder="Cari kode, nama, atau pemilik…" /></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Kode</th><th>Pangkalan</th><th>Pemilik</th><th>No. HP</th><th>Kuota / bulan</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($outlets as $outlet)
                        <tr>
                            <td class="doc-number">{{ $outlet->code }}</td>
                            <td>
                                <a href="{{ route('master.outlets.show', $outlet) }}" class="font-medium hover:text-gas">{{ $outlet->name }}</a>
                                <div class="max-w-xs truncate text-xs text-muted">{{ $outlet->address }}</div>
                            </td>
                            <td>{{ $outlet->owner_name ?: '—' }}</td>
                            <td class="num whitespace-nowrap">{{ $outlet->phone ?: '—' }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($outlet->quotas as $quota)
                                        <span class="rounded bg-paper px-1.5 py-0.5 text-xs whitespace-nowrap"><span class="text-muted">{{ $quota->cylinderType->code }}</span> <span class="num font-semibold">@num($quota->monthly_quota)</span></span>
                                    @empty
                                        <span class="text-xs text-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>@if ($outlet->is_active)<x-badge color="gas">Aktif</x-badge>@else<x-badge>Nonaktif</x-badge>@endif</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('master.outlets.show', $outlet) }}" class="btn-ghost btn btn-sm" title="Detail"><x-icon name="eye" class="size-4" /></a>
                                @can('master.manage')
                                    <a href="{{ route('master.outlets.edit', $outlet) }}" class="btn-ghost btn btn-sm" title="Ubah"><x-icon name="pencil" class="size-4" /></a>
                                    <x-delete-button :action="route('master.outlets.destroy', $outlet)" confirm="Hapus pangkalan {{ $outlet->name }}?" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="store" title="Belum ada pangkalan" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($outlets->hasPages())<div class="border-t border-line p-4">{{ $outlets->links() }}</div>@endif
    </div>
</x-layouts.app>
