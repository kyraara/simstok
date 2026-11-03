<x-layouts.app :title="$adjustment->number">
    <x-page-header :title="$adjustment->number" :subtitle="$adjustment->type->label()" :back="route('adjustments.index')">
        <x-slot:actions>
            @if ($adjustment->isPending())
                @can('adjustments.approve')
                    <x-reason-modal :action="route('adjustments.reject', $adjustment)" title="Tolak penyesuaian?" button="Tolak" confirm="Tolak" label="Alasan penolakan" icon="x" />
                    <form method="POST" action="{{ route('adjustments.approve', $adjustment) }}" onsubmit="return confirm('Setujui penyesuaian ini? Stok akan langsung diperbarui.')">
                        @csrf
                        <button class="btn btn-primary"><x-icon name="check" class="size-4" /> Setujui</button>
                    </form>
                @endcan
                @if (auth()->user()->can('adjustments.create') && ($adjustment->user_id === auth()->id() || auth()->user()->hasRole('admin')))
                    <form method="POST" action="{{ route('adjustments.cancel', $adjustment) }}" onsubmit="return confirm('Batalkan pengajuan ini?')">
                        @csrf
                        <button class="btn btn-secondary text-flame"><x-icon name="ban" class="size-4" /> Batalkan</button>
                    </form>
                @endif
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($adjustment->isPending())
        <div class="mb-6 flex items-start gap-3 rounded-card border border-amber/40 bg-amber/10 px-4 py-3 text-sm text-[#8a5413]">
            <x-icon name="clock" class="mt-0.5 size-5 shrink-0" />
            <div>Menunggu persetujuan Pimpinan. Stok <b>belum</b> berubah.</div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-muted">Status</dt><dd><x-status :value="$adjustment->status" /></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Tanggal</dt><dd class="font-medium">{{ $adjustment->date->translatedFormat('d F Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Diajukan oleh</dt><dd class="text-right font-medium">{{ $adjustment->user->name }}<div class="text-xs font-normal text-muted">{{ $adjustment->created_at->translatedFormat('d M Y H:i') }}</div></dd></div>
                @if ($adjustment->approver)
                    <div class="flex justify-between gap-4"><dt class="text-muted">{{ $adjustment->status === \App\Enums\ApprovalStatus::Rejected ? 'Ditolak' : 'Disetujui' }} oleh</dt>
                        <dd class="text-right font-medium">{{ $adjustment->approver->name }}<div class="text-xs font-normal text-muted">{{ $adjustment->approved_at?->translatedFormat('d M Y H:i') }}</div></dd></div>
                @endif
                @if ($adjustment->stockOpname)
                    <div class="flex justify-between gap-4"><dt class="text-muted">Dari opname</dt><dd><a href="{{ route('stock-opnames.show', $adjustment->stockOpname) }}" class="doc-number hover:text-gas">{{ $adjustment->stockOpname->number }}</a></dd></div>
                @endif
                <div><dt class="text-muted">Alasan</dt><dd class="mt-1 font-medium">{{ $adjustment->reason }}</dd></div>
                @if ($adjustment->review_note)
                    <div><dt class="text-muted">Catatan peninjau</dt><dd class="mt-1">{{ $adjustment->review_note }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="card overflow-hidden lg:col-span-2">
            <table class="table">
                <thead><tr><th>Jenis Tabung</th><th>Perubahan</th><th class="text-right">Jumlah</th></tr></thead>
                <tbody>
                    @foreach ($adjustment->items as $item)
                        <tr>
                            <td class="font-medium">{{ $item->cylinderType->name }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($item->from_condition)<x-status :value="$item->from_condition" /> <span class="num text-flame">−</span>@endif
                                    @if ($item->from_condition && $item->to_condition)<x-icon name="chevron-right" class="size-4 text-steel" />@endif
                                    @if ($item->to_condition)<x-status :value="$item->to_condition" /> <span class="num text-gas">+</span>@endif
                                </div>
                            </td>
                            <td class="num text-right font-semibold">@num($item->qty)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
