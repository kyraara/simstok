<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Generator nomor dokumen berformat PREFIX/YYYYMM/0001 (PN, SJ, PY, SO).
 *
 * Penghitung disimpan di tabel document_sequences dan dikunci saat dipakai,
 * sehingga dua transaksi bersamaan tidak akan mendapat nomor yang sama.
 */
class DocumentNumberService
{
    public function next(string $prefix, ?CarbonInterface $date = null): string
    {
        $period = ($date ?? now())->format('Ym');

        return DB::transaction(function () use ($prefix, $period) {
            $locked = fn () => DocumentSequence::query()
                ->where('prefix', $prefix)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            $sequence = $locked();

            if (! $sequence) {
                DocumentSequence::createOrFirst(['prefix' => $prefix, 'period' => $period], ['last_number' => 0]);
                $sequence = $locked();
            }

            $sequence->increment('last_number');

            return $this->format($prefix, $period, $sequence->last_number);
        });
    }

    /** Perkiraan nomor berikutnya untuk ditampilkan di form (tidak menaikkan penghitung). */
    public function preview(string $prefix, ?CarbonInterface $date = null): string
    {
        $period = ($date ?? now())->format('Ym');

        $last = DocumentSequence::where('prefix', $prefix)->where('period', $period)->value('last_number') ?? 0;

        return $this->format($prefix, $period, $last + 1);
    }

    private function format(string $prefix, string $period, int $number): string
    {
        return sprintf('%s/%s/%04d', $prefix, $period, $number);
    }
}
