<x-layouts.guest title="Akses Ditolak">
    <div class="flex min-h-screen items-center justify-center px-5">
        <div class="max-w-md text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-flame/10 text-flame"><x-icon name="ban" class="size-7" /></div>
            <div class="num mt-5 text-sm font-semibold text-flame">403</div>
            <h1 class="mt-1 text-2xl font-bold">Akses ditolak</h1>
            <p class="mt-2 text-muted">Peran Anda tidak memiliki hak untuk membuka halaman ini. Hubungi admin bila Anda memerlukan akses.</p>
            <a href="{{ url('/dashboard') }}" class="btn btn-primary mt-6">Kembali ke Dashboard</a>
        </div>
    </div>
</x-layouts.guest>
