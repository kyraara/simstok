<x-layouts.app title="Dashboard">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="text-2xl font-bold tracking-tight">Halo, {{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}</h1>
        </div>
        @can('transactions.create')
            <div class="hidden gap-2 sm:flex">
                <a href="{{ route('receipts.create') }}" class="btn btn-secondary"><x-icon name="receipt" class="size-4" /> Penerimaan</a>
                <a href="{{ route('distributions.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Distribusi</a>
            </div>
        @endcan
    </div>

    @if ($lowStock->isNotEmpty())
        <div class="mb-6 flex items-start gap-3 rounded-card border border-flame/30 bg-flame/5 px-4 py-3 text-sm" role="alert">
            <x-icon name="alert" class="mt-0.5 size-5 shrink-0 text-flame" />
            <div>
                <span class="font-semibold text-flame">Stok isi di bawah minimum:</span>
                @foreach ($lowStock as $t)
                    <span class="whitespace-nowrap">{{ $t->name }} (<span class="num">@num($t->stockOf('isi'))</span> / min <span class="num">@num($t->min_stock)</span>)</span>@if (! $loop->last), @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- F-17/F-18: kartu stok per jenis tabung --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($types as $type)
            @php
                $full = $type->stockOf('isi');
                $empty = $type->stockOf('kosong');
                $broken = $type->stockOf('rusak');
                $low = $type->isBelowMinimum();
                $scale = max($full, $type->min_stock * 3, 1);
            @endphp
            <a href="{{ route('stock-card', ['cylinder_type_id' => $type->id]) }}" @class(['card group block p-5 transition hover:shadow-md', 'ring-2 ring-flame/40' => $low])>
                <div class="flex items-center justify-between">
                    <div class="font-semibold">{{ $type->name }}</div>
                    @if ($low)
                        <x-badge color="flame">di bawah min</x-badge>
                    @else
                        <span class="text-xs text-muted">{{ $type->code }}</span>
                    @endif
                </div>

                <div class="mt-4 flex items-end justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold tracking-wider text-gas uppercase">Isi</div>
                        <div @class(['num text-3xl leading-none font-semibold tracking-tight', 'text-flame' => $low])>@num($full)</div>
                    </div>
                    @if ($type->min_stock > 0)
                        <div class="text-right text-xs text-muted">min <span class="num">@num($type->min_stock)</span></div>
                    @endif
                </div>
                <div class="relative mt-3 h-1.5 overflow-hidden rounded-full bg-paper">
                    <div @class(['h-full rounded-full', 'bg-flame' => $low, 'bg-gas' => ! $low]) style="width: {{ min(100, round($full / $scale * 100)) }}%"></div>
                    @if ($type->min_stock > 0)
                        <div class="absolute inset-y-0 w-px bg-ink/40" style="left: {{ min(100, round($type->min_stock / $scale * 100)) }}%" title="Stok minimum"></div>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-line pt-3">
                    <div>
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold tracking-wider text-steel uppercase"><span class="size-1.5 rounded-full bg-steel"></span>Kosong</div>
                        <div class="num mt-0.5 text-lg font-semibold">@num($empty)</div>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold tracking-wider text-flame uppercase"><span class="size-1.5 rounded-full bg-flame"></span>Rusak</div>
                        <div class="num mt-0.5 text-lg font-semibold">@num($broken)</div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Ringkasan hari ini --}}
    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Distribusi hari ini', $today['distributions'], 'surat jalan', 'distribution'],
            ['Isi keluar hari ini', $today['full_out'], 'tabung', 'distribution'],
            ['Penerimaan hari ini', $today['receipts'], 'dokumen', 'receipt'],
            ['Isi masuk hari ini', $today['full_in'], 'tabung', 'receipt'],
        ] as [$label, $value, $unit, $icon])
            <div class="card flex items-center gap-3 p-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-paper text-muted"><x-icon :name="$icon" class="size-5" /></div>
                <div class="min-w-0">
                    <div class="truncate text-xs text-muted">{{ $label }}</div>
                    <div><span class="num text-lg font-semibold">@num($value)</span> <span class="text-xs text-muted">{{ $unit }}</span></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- F-19: grafik distribusi 30 hari --}}
    <div class="card mt-6 p-5">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-bold">Distribusi 30 Hari Terakhir</h2>
            <span class="text-sm text-muted">Total <b class="num text-ink">@num($chart['total'])</b> tabung isi</span>
        </div>
        @if ($chart['total'] > 0)
            <div class="mt-4 h-72" x-data="distributionChart({ labels: @js($chart['labels']), datasets: @js($chart['datasets']) })">
                <canvas x-ref="canvas" role="img" aria-label="Grafik batang jumlah tabung isi yang didistribusikan per hari selama 30 hari terakhir"></canvas>
            </div>
        @else
            <x-empty icon="distribution" title="Belum ada distribusi dalam 30 hari terakhir" />
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        {{-- F-20: transaksi terbaru --}}
        <div class="card">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="font-bold">Transaksi Terbaru</h2>
                @can('reports.view')<a href="{{ route('stock-card') }}" class="text-sm font-medium text-gas hover:underline">Kartu stok →</a>@endcan
            </div>
            <ul class="divide-y divide-line">
                @forelse ($recent as $row)
                    <li>
                        <a href="{{ $row['url'] }}" class="flex items-center gap-3 px-5 py-3 hover:bg-paper/50">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-paper text-muted"><x-icon :name="$row['icon']" class="size-4" /></div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2"><span class="doc-number">{{ $row['number'] }}</span>
                                    @if ($row['status']->value !== 'posted' && $row['status']->value !== 'approved')<x-status :value="$row['status']" />@endif
                                </div>
                                <div class="truncate text-xs text-muted">{{ $row['kind'] }} · {{ $row['party'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="num text-sm font-semibold">{{ $row['qty'] }}</div>
                                <div class="text-xs text-muted">{{ $row['at']->diffForHumans() }}</div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li><x-empty title="Belum ada transaksi" /></li>
                @endforelse
            </ul>
        </div>

        {{-- Menunggu persetujuan --}}
        <div class="card" id="persetujuan">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="font-bold">Menunggu Persetujuan</h2>
                @php $pendingCount = $pendingAdjustments->count() + $pendingOpnames->count(); @endphp
                @if ($pendingCount)<x-badge color="amber">{{ $pendingCount }}</x-badge>@endif
            </div>
            <ul class="divide-y divide-line">
                @foreach ($pendingOpnames as $o)
                    <li>
                        <a href="{{ route('stock-opnames.show', $o) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-paper/50">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber/15 text-[#a8661a]"><x-icon name="opname" class="size-4" /></div>
                            <div class="min-w-0 flex-1">
                                <div class="doc-number">{{ $o->number }}</div>
                                <div class="truncate text-xs text-muted">Stok opname · {{ $o->user->name }}</div>
                            </div>
                            <x-icon name="chevron-right" class="size-4 text-steel" />
                        </a>
                    </li>
                @endforeach
                @foreach ($pendingAdjustments as $a)
                    <li>
                        <a href="{{ route('adjustments.show', $a) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-paper/50">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber/15 text-[#a8661a]"><x-icon name="adjustment" class="size-4" /></div>
                            <div class="min-w-0 flex-1">
                                <div class="doc-number">{{ $a->number }}</div>
                                <div class="truncate text-xs text-muted">{{ $a->type->label() }} · {{ $a->reason }}</div>
                            </div>
                            <x-icon name="chevron-right" class="size-4 text-steel" />
                        </a>
                    </li>
                @endforeach
                @if ($pendingCount === 0)
                    <li><x-empty icon="check-circle" title="Tidak ada yang menunggu">Semua penyesuaian dan opname sudah diproses.</x-empty></li>
                @endif
            </ul>
        </div>
    </div>
</x-layouts.app>
