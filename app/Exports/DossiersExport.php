<?php

namespace App\Exports;

use App\Models\Dossier;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DossiersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly array $filters = []
    ) {}

    public function title(): string
    {
        return 'Dossiers MAD';
    }

    public function query()
    {
        return Dossier::query()
            ->with([
                'client','user','fournisseur','transporteur',
                'etapeMadFournisseur','etapeFacturation',
                'etapeTransitaire','etapeLivraison','etapeCloture',
            ])
            ->when($this->filters['statut'] ?? null, fn($q, $v) => $q->where('statut', $v))
            ->when($this->filters['client_id'] ?? null, fn($q, $v) => $q->where('client_id', $v))
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Référence', 'Type', 'Responsable', 'N° Facture', 'Affaire',
            'Client', 'Pays', 'Fournisseur', 'Incoterm', 'Lieu Incoterm',
            'Transporteur', 'Poids (kg)', 'Coût Prévu', 'Coût Réel', 'Écart Coût',
            'Statut',
            // Étape 1
            'MAD Prévue', 'MAD Réelle', 'Écart MAD (j)',
            'Docs Reçus', 'Photos Reçues', 'COC Reçu', 'Obs. MAD Fourn.',
            // Étape 2
            'Facturé', 'Date Facturation', 'N° Facture Interne',
            'Paiement Reçu', 'Date Paiement', 'Montant', 'Devise', 'Obs. Facturation',
            // Étape 3
            'Transitaire Communiqué', 'Date Réception Infos',
            'Date Instructions', 'Date Enlèvement',
            'Temps Traitement (j)', 'Obs. Transitaire',
            // Étape 4
            'Livraison Prévue', 'Livraison Réelle', 'Écart Livraison (j)',
            'Mode Transport', 'AWB/BL', 'Motif Retard', 'Obs. Livraison',
            // Étape 5
            'POD Reçue', 'Date POD', 'Réf. POD', 'Source POD', 'Obs. Clôture',
        ];
    }

    public function map($d): array
    {
        $mad  = $d->etapeMadFournisseur;
        $fact = $d->etapeFacturation;
        $trans= $d->etapeTransitaire;
        $liv  = $d->etapeLivraison;
        $clot = $d->etapeCloture;

        $ecartCout = ($d->cout_transitaire && $d->cout_reel)
            ? round($d->cout_reel - $d->cout_transitaire, 2)
            : null;

        return [
            $d->reference,
            $d->type_commande,
            $d->user?->initiales,
            $d->numero_facture,
            $d->reference_affaire,
            $d->client?->nom,
            $d->pays_destination,
            $d->fournisseur?->nom,
            $d->incoterm_label,
            $d->incoterm_lieu,
            $d->transporteur?->nom,
            $d->poids,
            $d->cout_transitaire,
            $d->cout_reel,
            $ecartCout,
            $d->statut_label,
            // MAD
            $mad?->date_mad_prevue?->format('d/m/Y'),
            $mad?->date_mad_reelle?->format('d/m/Y'),
            $mad?->ecart_jours,
            $mad?->docs_recus    ? 'OUI' : 'NON',
            $mad?->photos_recues ? 'OUI' : 'NON',
            $mad?->coc_recu      ? 'OUI' : 'NON',
            $mad?->observations,
            // Facturation
            $fact?->facture_emise   ? 'OUI' : 'NON',
            $fact?->date_facturation?->format('d/m/Y'),
            $fact?->numero_facture_interne,
            $fact?->paiement_recu   ? 'OUI' : 'NON',
            $fact?->date_paiement?->format('d/m/Y'),
            $fact?->montant,
            $fact?->devise,
            $fact?->observations,
            // Transitaire
            $trans?->transitaire_communique ? 'OUI' : 'NON',
            $trans?->date_reception_infos_transitaire?->format('d/m/Y'),
            $trans?->date_instructions_envoyees?->format('d/m/Y'),
            $trans?->date_enlevement?->format('d/m/Y'),
            $trans?->temps_traitement_jours,
            $trans?->observations,
            // Livraison
            $liv?->date_livraison_prevue?->format('d/m/Y'),
            $liv?->date_livraison_reelle?->format('d/m/Y'),
            $liv?->ecart_jours,
            $liv?->mode_transport,
            $liv?->awb_bl_numero,
            $liv?->motif_retard,
            $liv?->observations,
            // Clôture
            $clot?->pod_recue  ? 'OUI' : 'NON',
            $clot?->date_pod?->format('d/m/Y'),
            $clot?->pod_reference,
            $clot?->pod_source,
            $clot?->observations,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A3EF5']],
            ],
        ];
    }
}
