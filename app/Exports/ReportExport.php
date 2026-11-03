<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Ekspor Excel generik untuk semua laporan.
 * Menerima struktur tabel dari ReportService (title, subtitle, headings, rows, totals).
 */
class ReportExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(private array $report) {}

    public function array(): array
    {
        $rows = [
            [$this->report['title']],
            [$this->report['subtitle']],
            [],
            $this->report['headings'],
            ...$this->report['rows'],
        ];

        if (! empty($this->report['totals'])) {
            $rows[] = $this->report['totals'];
        }

        return $rows;
    }

    public function title(): string
    {
        return mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', '', $this->report['title']), 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle("A4:{$lastColumn}4")->getFont()->setBold(true);
                $sheet->getStyle("A4:{$lastColumn}4")->getFill()->setFillType('solid')->getStartColor()->setRGB('F1EEE7');

                if (! empty($this->report['totals'])) {
                    $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->getFont()->setBold(true);
                }

                $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->freezePane('A5');
            },
        ];
    }
}
