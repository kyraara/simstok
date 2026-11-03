{{-- Lonceng notifikasi di top bar. Data awal dari server, lalu diperbarui tiap 30 detik. --}}
<div x-data="notificationBell({ feed: @js($notificationFeed ?? ['unread' => 0, 'items' => []]), url: @js(route('notifications.index')), readAllUrl: @js(route('notifications.read-all')) })"
    class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="toggle()" class="relative rounded-lg p-2 text-muted hover:bg-white hover:text-ink"
        :class="open && 'bg-white text-ink'" :aria-label="unread ? `${unread} notifikasi belum dibaca` : 'Notifikasi'">
        <x-icon name="bell" />
        <span x-show="unread > 0" x-cloak x-text="unread > 99 ? '99+' : unread"
            class="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-flame px-1 text-[10px] leading-4 font-bold text-white"></span>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right
        class="card fixed inset-x-3 top-16 z-40 overflow-hidden shadow-xl sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-96">
        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <div class="font-bold">Notifikasi</div>
            <button type="button" x-show="unread > 0" @click="readAll()" class="text-xs font-semibold text-gas hover:underline">Tandai semua dibaca</button>
        </div>

        <ul class="max-h-[60vh] divide-y divide-line overflow-y-auto">
            <template x-for="item in items" :key="item.id">
                <li>
                    <a :href="item.url" class="flex gap-3 px-4 py-3 hover:bg-paper/60" :class="!item.read && 'bg-amber/5'">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full"
                            :class="{ 'bg-amber/15 text-[#a8661a]': item.color === 'amber', 'bg-gas/10 text-gas': item.color === 'gas', 'bg-flame/10 text-flame': item.color === 'flame' }">
                            <template x-if="item.icon === 'adjustment'"><x-icon name="adjustment" class="size-4" /></template>
                            <template x-if="item.icon === 'opname'"><x-icon name="opname" class="size-4" /></template>
                            <template x-if="item.icon === 'alert'"><x-icon name="alert" class="size-4" /></template>
                            <template x-if="!['adjustment', 'opname', 'alert'].includes(item.icon)"><x-icon name="bell" class="size-4" /></template>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-sm" :class="item.read ? 'font-medium text-muted' : 'font-semibold text-ink'" x-text="item.title"></span>
                                <span x-show="!item.read" class="mt-1.5 size-2 shrink-0 rounded-full bg-flame"></span>
                            </span>
                            <span class="block truncate text-xs text-muted" x-text="item.message"></span>
                            <span class="mt-0.5 block text-[11px] text-steel" x-text="item.time"></span>
                        </span>
                    </a>
                </li>
            </template>
            <li x-show="items.length === 0" class="px-4 py-10 text-center text-sm text-muted">Belum ada notifikasi.</li>
        </ul>

        <a href="{{ route('notifications.index') }}" class="block border-t border-line px-4 py-2.5 text-center text-sm font-semibold text-gas hover:bg-paper/60">Lihat semua notifikasi</a>
    </div>
</div>
