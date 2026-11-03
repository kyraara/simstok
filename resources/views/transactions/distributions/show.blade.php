<x-layouts.app :title="$distribution->number">
    <x-page-header :title="$distribution->number" subtitle="Surat jalan distribusi ke pangkalan" :back="route('distributions.index')">
        <x-slot:actions>
            <a href="{{ route('distributions.print', $distribution) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" class="size-4" /> Cetak Surat Jalan</a>
            @if ($distribution->isPosted())
                @can('transactions.cancel')
                    <x-reason-modal :action="route('distributions.cancel', $distribution)" title="Batalkan distribusi?"
                        description="Stok isi akan dikembalikan dan stok kosong dikurangi sesuai jumlah di surat jalan ini. Tindakan ini tercatat di kartu stok."
                        button="Batalkan" confirm="Ya, batalkan" />
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('print'))
        <div x-data x-init="window.open(@js(route('distributions.print', $distribution)), '_blank')"
            class="mb-6 flex flex-col gap-3 rounded-card border border-gas/30 bg-gas/5 px-4 py-3 sm:flex-row sm:items-center">
            <x-icon name="printer" class="size-5 shrink-0 text-gas" />
            <div class="flex-1 text-sm">Surat jalan dibuka di tab baru. Bila tidak muncul karena diblokir browser, klik <b>Buka Surat Jalan</b>.</div>
            <a href="{{ route('distributions.print', $distribution) }}" target="_blank" class="btn btn-primary btn-sm">Buka Surat Jalan</a>
        </div>
    @endif

    @include('transactions.partials.cancel-banner', ['doc' => $distribution])

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-muted">Status</dt><dd><x-status :value="$distribution->status" /></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Tanggal</dt><dd class="font-medium">{{ $distribution->date->translatedFormat('l, d F Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Pangkalan</dt>
                    <dd class="text-right font-medium"><a href="{{ route('master.outlets.show', $distribution->outlet) }}" class="hover:text-gas">{{ $distribution->outlet->name }}</a>
                        <div class="text-xs font-normal text-muted">{{ $distribution->outlet->owner_name }}</div></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Kendaraan</dt><dd class="text-right font-medium">{{ $distribution->vehicle?->label() ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Dicatat oleh</dt><dd class="text-right font-medium">{{ $distribution->user->name }}<div class="text-xs font-normal text-muted">{{ $distribution->created_at->translatedFormat('d M Y H:i') }}</div></dd></div>
                @if ($distribution->note)
                    <div><dt class="text-muted">Catatan</dt><dd class="mt-1">{{ $distribution->note }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="card overflow-hidden lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Jenis Tabung</th><th class="text-right">Isi dikirim</th><th class="text-right">Kosong diterima</th><th class="text-right">Selisih</th><th class="text-right">Realisasi kuota</th></tr></thead>
                    <tbody>
                        @foreach ($distribution->items as $item)
                            @php $diff = $item->qty_full_out - $item->qty_empty_in; @endphp
                            @php $u = $usage[$item->cylinder_type_id] ?? null; @endphp
                            <tr>
                                <td class="font-medium">{{ $item->cylinderType->name }}</td>
                                <td class="num text-right font-semibold">−@num($item->qty_full_out)</td>
                                <td class="num text-right">+@num($item->qty_empty_in)</td>
                                <td class="num text-right {{ $diff ? 'text-[#a8661a]' : 'text-muted' }}" title="Tabung kosong yang belum dikembalikan pangkalan">{{ $diff ? number_format($diff, 0, ',', '.') : '—' }}</td>
                                <td class="num text-right text-sm text-muted">{{ $u ? number_format($u['used'], 0, ',', '.').' / '.number_format($u['quota'], 0, ',', '.') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
