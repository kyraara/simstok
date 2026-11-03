# SIMSTOK-LPG

Sistem Informasi Manajemen Stok Tabung Gas LPG untuk **PT Kalindo Goa Utama Gas**, proyek Kerja Praktik Teknik Informatika UIGM.

Dokumen acuan: [docs/PRD.md](docs/PRD.md) (kebutuhan) dan [docs/DESIGN.md](docs/DESIGN.md) (rancangan). Hasil pengujian ada di [docs/PENGUJIAN.md](docs/PENGUJIAN.md).

## Teknologi

Laravel 13 (PHP 8.3), MySQL 8, Blade + Tailwind CSS 4 + Alpine.js, Chart.js, spatie/laravel-permission, barryvdh/laravel-dompdf, maatwebsite/excel.

## Instalasi (Laragon / XAMPP)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database `simstok_lpg` (utf8mb4), sesuaikan `DB_*` di `.env`, lalu:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Buka http://localhost:8000.

Untuk data contoh (SPBE, 6 pangkalan + kuota, kendaraan, transaksi 30 hari terakhir):

```bash
php artisan db:seed --class=DemoSeeder
```

### Akun awal

Dibuat oleh `database/seeders/UserSeeder.php`, password awal semua akun `password`. **Ganti setelah instalasi** lewat menu Profil.

| Username | Peran |
|----------|-------|
| `admin` | Admin: semua fitur, kelola pengguna & master data |
| `gudang` | Petugas Gudang: input penerimaan, distribusi, penyesuaian, opname |
| `pimpinan` | Pimpinan: dashboard, laporan, menyetujui penyesuaian & opname |

### Saldo awal

Setelah instalasi, semua stok bernilai 0. Isi saldo awal dengan salah satu cara berikut:

- **Stok Opname** (disarankan): hitung fisik, ajukan, lalu disetujui Pimpinan.
- **Penyesuaian → Koreksi (+)**.

## Pengujian

```bash
php artisan test
```

Ada 70 tes (SQLite in-memory) yang mencakup 9 skenario black box di DESIGN.md §8, kriteria penerimaan PRD §10, hak akses, dan smoke test semua halaman/PDF/Excel.

## Arsitektur singkat

```
Route → Middleware (auth, active, permission) → Controller → FormRequest
      → Service (DB::transaction) → StockService → Model → MySQL
```

- **`app/Services/StockService.php`** adalah satu-satunya pintu yang mengubah tabel `stocks`. Setiap perubahan dikunci (`lockForUpdate`), dicek supaya tidak minus, dan dicatat di buku besar `stock_movements` dalam satu transaksi database.
- `ReceiptService`, `DistributionService`, `AdjustmentService`, dan `StockOpnameService` berisi aturan bisnis per transaksi.
- `DocumentNumberService` membuat nomor `PN/SJ/PY/SO/YYYYMM/0001` dari tabel penghitung yang dikunci, sehingga aman dari nomor ganda.
- `ReportService` menyiapkan data laporan dalam satu format, dipakai untuk tampilan layar, PDF, dan Excel.

### Keputusan rancangan (tambahan dari PRD/DESIGN)

| Hal | Keputusan |
|-----|-----------|
| Penyesuaian stok | Berstatus *pending*. Stok baru berubah setelah disetujui Pimpinan/Admin. |
| Selisih opname | Dihitung dari stok sistem **saat hitung fisik diajukan**, lalu dibukukan saat disetujui. Hasilnya tetap benar walau ada transaksi di antara keduanya. |
| Sesi opname | Hanya satu sesi terbuka (draft/diajukan) pada satu waktu. |
| Pembatalan | Transaksi tidak dihapus. Status jadi *batal* + alasan + siapa/kapan, dan stok dibalik lewat mutasi bertipe `pembatalan`. Pembatalan ditolak bila stok sudah terpakai (mis. tabung isi dari penerimaan sudah didistribusikan). |
| Kuota pangkalan | Hanya peringatan, tidak memblokir distribusi. |
| Master data | Data yang sudah dipakai transaksi tidak bisa dihapus, cukup dinonaktifkan. Pengguna tidak dihapus, hanya dinonaktifkan. |
| Login | Dengan username atau email, dibatasi 5 percobaan/menit. Akun nonaktif tidak bisa login dan sesinya langsung diputus. |
