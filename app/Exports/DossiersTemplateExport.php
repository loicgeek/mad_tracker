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

class DossiersTemplateExport extends BaseTemplateExport
{
    public function title(): string { return 'Dossiers'; }

    public function headings(): array
    {
        return [
            // Infos générales
            'reference', 'responsable', 'client', 'fournisseur',
            'numero_facture', 'reference_affaire', 'pays_destination',
            'incoterm', 'incoterm_lieu', 'transitaire_nom', 'transitaire_contact',
            'poids', 'cout_transitaire',
            // Étape 1 – MAD Fournisseur
            'mad_date_prevue', 'mad_date_reelle',
            'mad_docs_recus', 'mad_photos_recues', 'mad_coc_recu',
            'mad_date_docs_recus', 'mad_observations', 'mad_complete',
            // Étape 2 – Facturation
            'fact_emise', 'fact_date', 'fact_numero',
            'fact_paiement_recu', 'fact_date_paiement',
            'fact_montant', 'fact_devise', 'fact_observations', 'fact_complete',
            // Étape 3 – Transitaire
            'trans_communique', 'trans_date_reception',
            'trans_date_instructions', 'trans_date_enlevement',
            'trans_observations', 'trans_complete',
            // Étape 4 – Livraison
            'liv_date_prevue', 'liv_date_reelle',
            'liv_mode_transport', 'liv_awb',
            'liv_applicable', 'liv_observations', 'liv_complete',
            // Étape 5 – Clôture
            'clot_pod_recue', 'clot_date_pod',
            'clot_reference', 'clot_source',
            'clot_observations', 'clot_complete',
        ];
    }

    protected function exampleRows(): array
    {
        return [
            [
                'DOS-2024-0001', 'MSB', 'PERENCO', 'Masoneilan AF-Sud',
                'DB20/123', '190923-003AG', 'Cameroun',
                'FCA_USINE', 'Condé-sur-Noireau', 'Bolloré', 'contact@bollore.com',
                '150.5', '850',
                '15/01/2024', '17/01/2024',
                'OUI', 'OUI', 'OUI',
                '18/01/2024', '', 'OUI',
                'OUI', '20/01/2024', 'FAKT-2024-001',
                'OUI', '05/02/2024',
                '12500', 'EUR', '', 'OUI',
                'OUI', '22/01/2024',
                '23/01/2024', '25/01/2024',
                '', 'OUI',
                '10/02/2024', '14/02/2024',
                'aérien', 'AWB123456789',
                'OUI', '', 'OUI',
                'OUI', '20/02/2024',
                'POD-REF-001', 'DHL',
                '', 'OUI',
            ],
            [
                '', 'CA', 'TOTAL', 'Bently Nevada',
                'DB20/456', 'AFFAIRE-002', 'République Du Congo',
                'CPT', 'Paris CDG', '', '',
                '', '',
                '01/03/2024', '',
                'NON', 'NON', 'NON',
                '', 'En attente docs', 'NON',
                'NON', '', '',
                'NON', '',
                '', 'EUR', '', 'NON',
                'NON', '',
                '', '',
                '', 'NON',
                '', '',
                '', '',
                'OUI', '', 'NON',
                'NON', '',
                '', '',
                '', 'NON',
            ],
        ];
    }
}