@props(['title' => null])
@php
    $user = auth()->user();
    $nav = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'can' => null],
        ['route' => 'receipts.index', 'match' => 'receipts.*', 'label' => 'Penerimaan SPBE', 'icon' => 'receipt', 'can' => 'transactions.view'],
        ['route' => 'distributions.index', 'match' => 'distributions.*', 'label' => 'Distribusi', 'icon' => 'distribution', 'can' => 'transactions.view'],
        ['route' => 'adjustments.index', 'match' => 'adjustments.*', 'label' => 'Penyesuaian', 'icon' => 'adjustment', 'can' => 'transactions.view'],
        ['route' => 'stock-opnames.index', 'match' => 'stock-opnames.*', 'label' => 'Stok Opname', 'icon' => 'opname', 'can' => 'transactions.view'],
        ['route' => 'stock-card', 'match' => 'stock-card', 'label' => 'Kartu Stok', 'icon' => 'stock-card', 'can' => 'reports.view'],
        ['route' => 'reports.index', 'match' => 'reports.*', 'label' => 'Laporan', 'icon' => 'report', 'can' => 'reports.view'],
    ];
    $master = [
        ['route' => 'master.cylinder-types.index', 'match' => 'master.cylinder-types.*', 'label' => 'Jenis Tabung', 'icon' => 'cylinder', 'can' => 'master.view'],
        ['route' => 'master.suppliers.index', 'match' => 'master.suppliers.*', 'label' => 'SPBE / Pemasok', 'icon' => 'factory', 'can' => 'master.view'],
        ['route' => 'master.outlets.index', 'match' => 'master.outlets.*', 'label' => 'Pangkalan', 'icon' => 'store', 'can' => 'master.view'],
        ['route' => 'master.vehicles.index', 'match' => 'master.vehicles.*', 'label' => 'Kendaraan', 'icon' => 'truck', 'can' => 'master.view'],
        ['route' => 'users.index', 'match' => 'users.*', 'label' => 'Pengguna', 'icon' => 'users', 'can' => 'users.manage'],
    ];
    $visible = fn ($items) => array_filter($items, fn ($i) => ! $i['can'] || $user->can($i['can']));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#14213d">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data="{ nav: false }" @keydown.escape.window="nav = false">

    {{-- Overlay drawer (HP) --}}
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-ink/40 lg:hidden" @click="nav = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-ink text-white transition-transform duration-200 lg:translate-x-0"
        :class="nav && 'translate-x-0'">
        <div class="flex h-16 shrink-0 items-center gap-3 px-5">
            <x-logo class="size-10" />
            <div class="leading-tight">
                <div class="font-bold tracking-tight">SIMSTOK-LPG</div>
                <div class="text-[11px] text-white/50">PT Kalindo Goa Utama Gas</div>
            </div>
            <button type="button" class="ml-auto rounded-md p-1 text-white/60 hover:text-white lg:hidden" @click="nav = false" aria-label="Tutup menu">
                <x-icon name="x" />
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
            <div class="space-y-0.5">
                @foreach ($visible($nav) as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}" @class([
                        'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-white/10 text-white' => $active,
                        'text-white/65 hover:bg-white/5 hover:text-white' => ! $active,
                    ])>
                        <x-icon :name="$item['icon']" @class(['size-[18px]', 'text-gas' => $active]) />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            @if (count($visible($master)))
                <div>
                    <div class="mb-1 px-3 text-[11px] font-semibold tracking-wider text-white/40 uppercase">Master Data</div>
                    <div class="space-y-0.5">
                        @foreach ($visible($master) as $item)
                            @php $active = request()->routeIs($item['match']); @endphp
                            <a href="{{ route($item['route']) }}" @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-white/10 text-white' => $active,
                                'text-white/65 hover:bg-white/5 hover:text-white' => ! $active,
                            ])>
                                <x-icon :name="$item['icon']" @class(['size-[18px]', 'text-gas' => $active]) />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </nav>

        <div class="border-t border-white/10 p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-semibold">{{ $user->initials() }}</div>
                <div class="min-w-0 flex-1 leading-tight">
                    <div class="truncate text-sm font-semibold">{{ $user->name }}</div>
                    <div class="truncate text-xs text-white/50">{{ $user->roleLabel() }}</div>
                </div>
            </div>
        </div>
    </aside>

    <div class="lg:pl-64">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-paper/90 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button type="button" class="-ml-1 rounded-md p-1.5 text-ink hover:bg-white lg:hidden" @click="nav = true" aria-label="Buka menu">
                <x-icon name="menu" />
            </button>
            <div class="truncate text-sm font-semibold text-muted">{{ $title }}</div>

            <div class="ml-auto flex items-center gap-1">
                @include('notifications.bell')

                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-lg py-1.5 pr-2 pl-1.5 text-sm font-semibold hover:bg-white">
                        <span class="flex size-7 items-center justify-center rounded-full bg-ink text-xs text-white">{{ $user->initials() }}</span>
                        <span class="hidden sm:inline">{{ \Illuminate\Support\Str::words($user->name, 2, '') }}</span>
                        <x-icon name="chevron-down" class="size-4 text-muted" />
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right class="card absolute right-0 mt-2 w-56 p-1.5 shadow-lg">
                        <div class="border-b border-line px-3 pt-1.5 pb-2.5">
                            <div class="text-sm font-semibold">{{ $user->name }}</div>
                            <div class="text-xs text-muted">{{ '@'.$user->username }} · {{ $user->roleLabel() }}</div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="mt-1 flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-paper">
                            <x-icon name="user" class="size-4 text-muted" /> Profil & Password
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-flame hover:bg-flame/5">
                                <x-icon name="logout" class="size-4" /> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <x-flash />
            {{ $slot }}
        </main>
    </div>

    {{-- Aksi cepat untuk petugas gudang di HP --}}
    @can('transactions.create')
        @unless (request()->routeIs('distributions.create'))
            <a href="{{ route('distributions.create') }}"
                class="fixed right-4 bottom-5 z-30 inline-flex items-center gap-2 rounded-full bg-gas px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-gas/30 hover:bg-gas-dark lg:hidden">
                <x-icon name="plus" class="size-5" /> Distribusi
            </a>
        @endunless
    @endcan

    @stack('scripts')
</body>
</html>
