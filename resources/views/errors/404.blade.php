<x-layouts.guest title="Tidak Ditemukan">
    <div class="flex min-h-screen items-center justify-center px-5">
        <div class="max-w-md text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-steel/15 text-steel"><x-icon name="search" class="size-7" /></div>
            <div class="num mt-5 text-sm font-semibold text-steel">404</div>
            <h1 class="mt-1 text-2xl font-bold">Halaman tidak ditemukan</h1>
            <p class="mt-2 text-muted">Alamat yang Anda buka tidak ada atau datanya sudah dihapus.</p>
            <a href="{{ url('/dashboard') }}" class="btn btn-primary mt-6">Kembali ke Dashboard</a>
        </div>
    </div>
</x-layouts.guest>
