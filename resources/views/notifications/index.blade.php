<x-layouts.app title="Notifikasi">
    <x-page-header title="Notifikasi" subtitle="Pengajuan yang perlu disetujui, hasil persetujuan, dan peringatan stok.">
        <x-slot:actions>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-secondary"><x-icon name="check" class="size-4" /> Tandai semua dibaca</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @php
        $styles = ['amber' => 'bg-amber/15 text-[#a8661a]', 'gas' => 'bg-gas/10 text-gas', 'flame' => 'bg-flame/10 text-flame'];
    @endphp

    <div class="card">
        <ul class="divide-y divide-line">
            @forelse ($notifications as $n)
                <li>
                    <a href="{{ route('notifications.open', $n->id) }}" @class(['flex gap-4 px-5 py-4 hover:bg-paper/60', 'bg-amber/5' => ! $n->read_at])>
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $styles[$n->data['color'] ?? 'amber'] ?? $styles['amber'] }}">
                            <x-icon :name="$n->data['icon'] ?? 'bell'" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span @class(['font-semibold text-ink' => ! $n->read_at, 'font-medium text-muted' => $n->read_at])>{{ $n->data['title'] ?? '' }}</span>
                                @unless ($n->read_at)<span class="size-2 rounded-full bg-flame"></span>@endunless
                            </span>
                            <span class="block text-sm text-muted">{{ $n->data['message'] ?? '' }}</span>
                        </span>
                        <span class="shrink-0 text-right text-xs text-steel" title="{{ $n->created_at->translatedFormat('d M Y H:i') }}">{{ $n->created_at->diffForHumans() }}</span>
                    </a>
                </li>
            @empty
                <li><x-empty icon="bell" title="Belum ada notifikasi">Notifikasi muncul saat ada pengajuan yang perlu disetujui, hasil persetujuan, atau stok turun di bawah minimum.</x-empty></li>
            @endforelse
        </ul>
        @if ($notifications->hasPages())<div class="border-t border-line p-4">{{ $notifications->links() }}</div>@endif
    </div>
</x-layouts.app>
