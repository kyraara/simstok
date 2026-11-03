<?php

namespace App\Services;

use App\Enums\StockCondition;
use App\Enums\TransactionStatus;
use App\Models\CylinderType;
use App\Models\Distribution;
use App\Models\DistributionItem;
use App\Models\Outlet;
use App\Models\OutletQuota;
use App\Models\Receipt;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data laporan. Setiap laporan menghasilkan struktur tabel yang sama
 * (title, subtitle, headings, rows, totals, numeric) sehingga bisa
 * ditampilkan di layar, PDF, maupun Excel tanpa logika ganda.
 */
class ReportService
{
    /**
     * F-21: kartu stok satu jenis tabung + kondisi pada suatu periode.
     *
     * @return array{opening: int, closing: int, in: int, out: int, movements: Collection}
     */
    public function stockCard(int $cylinderTypeId, StockCondition $condition, Carbon $from, Carbon $to): array
    {
        $base = StockMovement::query()
            ->where('cylinder_type_id', $cylinderTypeId)
            ->where('condition', $condition);

        $opening = (int) (clone $base)->where('moved_at', '<', $from->copy()->startOfDay())->sum('qty');

        $movements = (clone $base)
            ->with([
                'reference' => fn (MorphTo $morph) => $morph->morphWith([
                    Distribution::class => ['outlet'],
                    Receipt::class => ['supplier'],
                ]),
                'user',
            ])
            ->whereBetween('moved_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('moved_at')->orderBy('id')
            ->get();

        $balance = $opening;
        foreach ($movements as $movement) {
            $balance += $movement->qty;
            $movement->running_balance = $balance;
        }

        return [
            'opening' => $opening,
            'closing' => $balance,
            'in' => (int) $movements->where('qty', '>', 0)->sum('qty'),
            'out' => (int) -$movements->where('qty', '<', 0)->sum('qty'),
            'movements' => $movements,
        ];
    }

    public function stockCardTable(CylinderType $type, StockCondition $condition, Carbon $from, Carbon $to): array
    {
        $card = $this->stockCard($type->id, $condition, $from, $to);

        $rows = [['', '', 'Saldo awal', '', '', $card['opening'], '']];
        foreach ($card['movements'] as $m) {
            $rows[] = [
                $m->moved_at->format('d/m/Y H:i'),
                $m->reference?->number ?? '-',
                $m->type->label().($m->note ? ' — '.$m->note : ''),
                $m->qty > 0 ? $m->qty : '',
                $m->qty < 0 ? -$m->qty : '',
                $m->running_balance,
                $m->user?->name ?? '-',
            ];
        }

        return [
            'title' => 'Kartu Stok '.$type->name.' — '.$condition->label(),
            'subtitle' => $this->periodLabel($from, $to),
            'headings' => ['Waktu', 'No. Dokumen', 'Keterangan', 'Masuk', 'Keluar', 'Saldo', 'Petugas'],
            'rows' => $rows,
            'totals' => ['', '', 'Total / saldo akhir', $card['in'], $card['out'], $card['closing'], ''],
            'numeric' => [3, 4, 5],
        ];
    }

    /** F-22: rekap penerimaan dari SPBE per dokumen, kolom per jenis tabung. */
    public function receipts(Carbon $from, Carbon $to, ?int $supplierId = null): array
    {
        $types = $this->types();

        $receipts = Receipt::query()
            ->with(['supplier', 'items'])
            ->where('status', TransactionStatus::Posted)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->orderBy('date')->orderBy('id')
            ->get();

        $headings = ['Tanggal', 'No. Penerimaan', 'No. LO/DO', 'SPBE'];
        foreach ($types as $t) {
            $headings[] = $t->code.' isi';
            $headings[] = $t->code.' kosong';
        }

        $rows = [];
        $totals = array_fill(0, $types->count() * 2, 0);
        foreach ($receipts as $r) {
            $items = $r->items->keyBy('cylinder_type_id');
            $row = [$r->date->format('d/m/Y'), $r->number, $r->ref_document ?: '-', $r->supplier->name];
            foreach ($types->values() as $i => $t) {
                $full = (int) ($items[$t->id]->qty_full_in ?? 0);
                $empty = (int) ($items[$t->id]->qty_empty_out ?? 0);
                $row[] = $full;
                $row[] = $empty;
                $totals[$i * 2] += $full;
                $totals[$i * 2 + 1] += $empty;
            }
            $rows[] = $row;
        }

        return [
            'title' => 'Rekap Penerimaan dari SPBE',
            'subtitle' => $this->periodLabel($from, $to).($supplierId ? ' · '.Supplier::find($supplierId)?->name : ' · Semua SPBE'),
            'headings' => $headings,
            'rows' => $rows,
            'totals' => array_merge(['', '', '', 'Total ('.count($rows).' dokumen)'], $totals),
            'numeric' => range(4, 3 + count($totals)),
        ];
    }

    /** F-23: rekap distribusi per pangkalan, dengan realisasi terhadap kuota. */
    public function distributions(Carbon $from, Carbon $to, ?int $outletId = null): array
    {
        $types = $this->types();
        $months = $this->monthCount($from, $to);

        $sums = DistributionItem::query()
            ->join('distributions', 'distributions.id', '=', 'distribution_items.distribution_id')
            ->where('distributions.status', TransactionStatus::Posted->value)
            ->whereBetween('distributions.date', [$from->toDateString(), $to->toDateString()])
            ->when($outletId, fn ($q) => $q->where('distributions.outlet_id', $outletId))
            ->groupBy('distributions.outlet_id', 'distribution_items.cylinder_type_id')
            ->selectRaw('distributions.outlet_id, distribution_items.cylinder_type_id, SUM(qty_full_out) as full_out, SUM(qty_empty_in) as empty_in')
            ->get()
            ->groupBy('outlet_id');

        $tripCounts = Distribution::query()
            ->where('status', TransactionStatus::Posted)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->groupBy('outlet_id')
            ->selectRaw('outlet_id, COUNT(*) as trips')
            ->pluck('trips', 'outlet_id');

        $quotas = OutletQuota::query()->get()->groupBy('outlet_id')->map(fn ($q) => $q->pluck('monthly_quota', 'cylinder_type_id'));

        $outlets = Outlet::query()
            ->when($outletId, fn ($q) => $q->whereKey($outletId), fn ($q) => $q->whereIn('id', $sums->keys()))
            ->orderBy('name')
            ->get();

        $headings = ['Kode', 'Pangkalan', 'Pengiriman'];
        foreach ($types as $t) {
            $headings[] = $t->code.' isi';
            $headings[] = $t->code.' kuota';
        }
        $headings[] = 'Kosong kembali';
        $headings[] = 'Belum kembali';

        $rows = [];
        $totals = array_fill(0, $types->count() * 2 + 3, 0);
        foreach ($outlets as $outlet) {
            $data = ($sums[$outlet->id] ?? collect())->keyBy('cylinder_type_id');
            $row = [$outlet->code, $outlet->name, (int) ($tripCounts[$outlet->id] ?? 0)];
            $totals[0] += $row[2];
            $fullTotal = 0;
            $emptyTotal = 0;
            foreach ($types->values() as $i => $t) {
                $full = (int) ($data[$t->id]->full_out ?? 0);
                $quota = (int) ($quotas[$outlet->id][$t->id] ?? 0) * $months;
                $row[] = $full;
                $row[] = $quota ?: '';
                $totals[1 + $i * 2] += $full;
                $totals[2 + $i * 2] += $quota;
                $fullTotal += $full;
                $emptyTotal += (int) ($data[$t->id]->empty_in ?? 0);
            }
            $row[] = $emptyTotal;
            $row[] = $fullTotal - $emptyTotal;
            $totals[count($totals) - 2] += $emptyTotal;
            $totals[count($totals) - 1] += $fullTotal - $emptyTotal;
            $rows[] = $row;
        }

        return [
            'title' => 'Rekap Distribusi per Pangkalan',
            'subtitle' => $this->periodLabel($from, $to).($outletId ? ' · '.Outlet::find($outletId)?->name : ' · Semua pangkalan')
                .($months > 1 ? " · kuota dikali {$months} bulan" : ''),
            'headings' => $headings,
            'rows' => $rows,
            'totals' => array_merge(['', 'Total'], $totals),
            'numeric' => range(2, 1 + count($totals)),
        ];
    }

    /** F-25: posisi stok per tanggal, dihitung dari buku besar mutasi. */
    public function stockPosition(Carbon $date): array
    {
        $types = $this->types();

        $balances = StockMovement::query()
            ->where('moved_at', '<=', $date->copy()->endOfDay())
            ->groupBy('cylinder_type_id', 'condition')
            ->selectRaw('cylinder_type_id, `condition`, SUM(qty) as total')
            ->get()
            ->groupBy('cylinder_type_id')
            ->map(fn ($rows) => $rows->mapWithKeys(fn ($r) => [$r->condition->value => (int) $r->total]));

        $rows = [];
        $totals = [0, 0, 0, 0];
        foreach ($types as $t) {
            $b = $balances[$t->id] ?? collect();
            $row = [$t->code, $t->name, $b['isi'] ?? 0, $b['kosong'] ?? 0, $b['rusak'] ?? 0];
            $row[] = $row[2] + $row[3] + $row[4];
            foreach ([2, 3, 4, 5] as $i => $col) {
                $totals[$i] += $row[$col];
            }
            $rows[] = $row;
        }

        return [
            'title' => 'Posisi Stok per Tanggal',
            'subtitle' => 'Posisi akhir hari '.$date->translatedFormat('l, d F Y'),
            'headings' => ['Kode', 'Jenis Tabung', 'Isi', 'Kosong', 'Rusak', 'Total Tabung'],
            'rows' => $rows,
            'totals' => array_merge(['', 'Total'], $totals),
            'numeric' => [2, 3, 4, 5],
        ];
    }

    public function periodLabel(Carbon $from, Carbon $to): string
    {
        return 'Periode '.$from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y');
    }

    private function types(): Collection
    {
        return CylinderType::orderBy('weight_kg')->get();
    }

    private function monthCount(Carbon $from, Carbon $to): int
    {
        return ($to->year - $from->year) * 12 + $to->month - $from->month + 1;
    }
}
