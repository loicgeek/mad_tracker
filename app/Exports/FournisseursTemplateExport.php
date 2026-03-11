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

// ─── Fournisseurs template ────────────────────────────────────────────────────

class FournisseursTemplateExport extends BaseTemplateExport
{
    public function title(): string { return 'Fournisseurs'; }

    public function headings(): array
    {
        return ['nom', 'pays', 'ville', 'contact_nom', 'contact_email', 'notes'];
    }

    protected function exampleRows(): array
    {
        return [
            ['Masoneilan AF-Sud', 'France', 'Condé-sur-Noireau', 'contact@masoneilan.fr', '', ''],
            ['Bently Nevada', 'USA', 'Minden', '', 'contact@bentlynevada.com', 'Capteurs'],
        ];
    }
}