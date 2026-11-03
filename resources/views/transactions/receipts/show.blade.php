<x-layouts.app :title="$receipt->number">
    <x-page-header :title="$receipt->number" subtitle="Penerimaan dari SPBE" :back="route('receipts.index')">
        <x-slot:actions>
            @if ($receipt->isPosted())
                @can('transactions.cancel')
                    <x-reason-modal :action="route('receipts.cancel', $receipt)" title="Batalkan penerimaan?"
                        description="Stok isi akan dikurangi dan stok kosong dikembalikan sesuai jumlah di dokumen ini. Tindakan ini tercatat di kartu stok."
                        button="Batalkan" confirm="Ya, batalkan" />
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    @include('transactions.partials.cancel-banner', ['doc' => $receipt])

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-muted">Status</dt><dd><x-status :value="$receipt->status" /></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Tanggal</dt><dd class="font-medium">{{ $receipt->date->translatedFormat('l, d F Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">SPBE</dt><dd class="text-right font-medium">{{ $receipt->supplier->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">No. LO/DO</dt><dd class="num font-medium">{{ $receipt->ref_document ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Kendaraan</dt><dd class="text-right font-medium">{{ $receipt->vehicle?->label() ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Dicatat oleh</dt><dd class="text-right font-medium">{{ $receipt->user->name }}<div class="text-xs font-normal text-muted">{{ $receipt->created_at->translatedFormat('d M Y H:i') }}</div></dd></div>
                @if ($receipt->note)
                    <div><dt class="text-muted">Catatan</dt><dd class="mt-1">{{ $receipt->note }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="card overflow-hidden lg:col-span-2">
            <table class="table">
                <thead><tr><th>Jenis Tabung</th><th class="text-right">Isi diterima</th><th class="text-right">Kosong dikirim</th></tr></thead>
                <tbody>
                    @foreach ($receipt->items as $item)
                        <tr>
                            <td class="font-medium">{{ $item->cylinderType->name }}</td>
                            <td class="num text-right font-semibold text-gas">+@num($item->qty_full_in)</td>
                            <td class="num text-right">−@num($item->qty_empty_out)</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-paper/60 font-bold">
                        <td class="px-4 py-3">Total</td>
                        <td class="num px-4 py-3 text-right text-gas">+@num($receipt->items->sum('qty_full_in'))</td>
                        <td class="num px-4 py-3 text-right">−@num($receipt->items->sum('qty_empty_out'))</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-layouts.app>
