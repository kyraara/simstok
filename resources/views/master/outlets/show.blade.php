<x-layouts.app :title="$outlet->name">
    <x-page-header :title="$outlet->name" :subtitle="$outlet->code.' · '.($outlet->owner_name ?: 'Pemilik belum diisi')" :back="route('master.outlets.index')">
        <x-slot:actions>
            @can('master.manage')
                <a href="{{ route('master.outlets.edit', $outlet) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Ubah</a>
            @endcan
            <a href="{{ route('reports.distributions', ['outlet_id' => $outlet->id]) }}" class="btn btn-secondary"><x-icon name="report" class="size-4" /> Rekap Distribusi</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6">
            <h2 class="font-bold">Info Pangkalan</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-muted">Alamat</dt><dd class="font-medium">{{ $outlet->address ?: '—' }}</dd></div>
                <div><dt class="text-muted">No. HP</dt><dd class="num font-medium">{{ $outlet->phone ?: '—' }}</dd></div>
                <div><dt class="text-muted">Status</dt><dd>@if ($outlet->is_active)<x-badge color="gas">Aktif</x-badge>@else<x-badge>Nonaktif</x-badge>@endif</dd></div>
            </dl>
        </div>

        <div class="card p-6 lg:col-span-2">
            <div class="flex items-baseline justify-between">
                <h2 class="font-bold">Realisasi Kuota {{ now()->translatedFormat('F Y') }}</h2>
            </div>
            <div class="mt-5 space-y-5">
                @foreach ($types as $type)
                    @continue(! isset($usage[$type->id]))
                    @php $u = $usage[$type->id]; @endphp
                    @php $pct = $u['quota'] > 0 ? min(100, round($u['used'] / $u['quota'] * 100)) : 0; @endphp
                    <div>
                        <div class="mb-1.5 flex items-baseline justify-between text-sm">
                            <span class="font-medium">{{ $type->name }}</span>
                            <span class="num"><b>@num($u['used'])</b> <span class="text-muted">/ @num($u['quota'])</span></span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-paper">
                            <div @class(['h-full rounded-full', 'bg-gas' => $u['used'] <= $u['quota'], 'bg-flame' => $u['used'] > $u['quota']]) style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
                @if (empty($usage))
                    <p class="text-sm text-muted">Pangkalan ini belum memiliki kuota.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="border-b border-line px-6 py-4"><h2 class="font-bold">Distribusi Terakhir</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Surat Jalan</th><th>Tanggal</th><th>Item</th></tr></thead>
                <tbody>
                    @forelse ($distributions as $d)
                        <tr>
                            <td><a href="{{ route('distributions.show', $d) }}" class="doc-number hover:text-gas">{{ $d->number }}</a></td>
                            <td class="whitespace-nowrap">{{ $d->date->translatedFormat('d M Y') }}</td>
                            <td class="text-sm">
                                @foreach ($d->items as $item)
                                    <span class="mr-3 whitespace-nowrap">{{ $item->cylinderType->code }} <b class="num">@num($item->qty_full_out)</b></span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-empty icon="distribution" title="Belum ada distribusi" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
