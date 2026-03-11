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


// ─── Clients template ─────────────────────────────────────────────────────────

class ClientsTemplateExport extends BaseTemplateExport
{
    public function title(): string { return 'Clients'; }

    public function headings(): array
    {
        return ['nom', 'pays', 'contact_nom', 'contact_email', 'contact_phone', 'notes'];
    }

    protected function exampleRows(): array
    {
        return [
            ['PERENCO', 'Cameroun', 'Jean Dupont', 'j.dupont@perenco.com', '+237 6XX XX XX XX', ''],
            ['TOTAL', 'République Du Congo', '', '', '', 'Client VIP'],
        ];
    }
}