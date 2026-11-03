<x-layouts.app title="Laporan">
    <x-page-header title="Laporan" subtitle="Semua laporan bisa dilihat di layar dan diunduh sebagai PDF atau Excel." />

    @php
        $reports = [
            ['route' => 'stock-card', 'icon' => 'stock-card', 'title' => 'Kartu Stok', 'desc' => 'Mutasi masuk/keluar per jenis tabung dan kondisi, lengkap dengan saldo berjalan.', 'code' => 'F-21'],
            ['route' => 'reports.receipts', 'icon' => 'receipt', 'title' => 'Rekap Penerimaan SPBE', 'desc' => 'Tabung isi diterima dan kosong dikirim per dokumen LO/DO.', 'code' => 'F-22'],
            ['route' => 'reports.distributions', 'icon' => 'store', 'title' => 'Rekap Distribusi per Pangkalan', 'desc' => 'Realisasi distribusi dibanding kuota bulanan, termasuk tabung kosong yang belum kembali.', 'code' => 'F-23'],
            ['route' => 'stock-opnames.index', 'icon' => 'opname', 'title' => 'Stok Opname & Selisih', 'desc' => 'Hasil hitung fisik dibanding stok sistem per sesi opname (PDF).', 'code' => 'F-24'],
            ['route' => 'reports.stock-position', 'icon' => 'cylinder', 'title' => 'Posisi Stok per Tanggal', 'desc' => 'Saldo isi, kosong, dan rusak pada akhir hari tanggal tertentu.', 'code' => 'F-25'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($reports as $r)
            <a href="{{ route($r['route']) }}" class="card group flex flex-col p-5 transition hover:border-gas/40 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-gas/10 text-gas"><x-icon :name="$r['icon']" class="size-5" /></div>
                    <span class="font-mono text-[11px] text-steel">{{ $r['code'] }}</span>
                </div>
                <div class="mt-4 font-bold group-hover:text-gas">{{ $r['title'] }}</div>
                <p class="mt-1 text-sm text-muted">{{ $r['desc'] }}</p>
            </a>
        @endforeach
    </div>
</x-layouts.app>
