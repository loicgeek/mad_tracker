<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// ─── Base template ────────────────────────────────────────────────────────────

abstract class BaseTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    abstract public function headings(): array;
    abstract public function title(): string;

    /** Return 1-2 example rows */
    abstract protected function exampleRows(): array;

    public function array(): array { return $this->exampleRows(); }

    public function styles(Worksheet $sheet): array
    {
        // Header row: blue bg, white bold text
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headings()));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A3EF5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Example rows: light gray
        $sheet->getStyle("A2:{$lastCol}3")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
            'font' => ['italic' => true, 'color' => ['argb' => 'FF64748B']],
        ]);

        // Freeze header
        $sheet->freezePane('A2');

        return [];
    }
}





// ─── Dossiers template ────────────────────────────────────────────────────────


