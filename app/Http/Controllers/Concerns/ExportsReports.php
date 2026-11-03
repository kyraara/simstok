<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/** Ekspor laporan ke PDF/Excel berdasarkan parameter ?export=pdf|xlsx. */
trait ExportsReports
{
    protected function export(Request $request, array $report): ?Response
    {
        $filename = Str::slug($report['title']).'-'.now()->format('Ymd-His');

        return match ($request->query('export')) {
            'pdf' => Pdf::loadView('pdf.report', ['report' => $report])
                ->setPaper('a4', count($report['headings']) > 7 ? 'landscape' : 'portrait')
                ->download($filename.'.pdf'),
            'xlsx' => Excel::download(new ReportExport($report), $filename.'.xlsx'),
            default => null,
        };
    }

    /** @return array{0: Carbon, 1: Carbon} periode dari query string; bawaan: bulan berjalan */
    protected function period(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to) : now();

        return [$from->startOfDay(), $to->startOfDay()];
    }
}
