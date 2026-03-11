<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\User;
use App\Models\Dossier;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DossiersImport implements ToCollection, WithHeadingRow, SkipsOnError, WithChunkReading
{
    use WithImportTracking, SkipsErrors;

    /** Process in chunks of 100 rows to avoid memory issues */
    public function chunkSize(): int { return 100; }

    /**
     * Expected columns (maps to your SYNTHESE_MAD.xlsx BASE sheet):
     *
     * Infos générales:
     *   reference, responsable (initiales), client, fournisseur,
     *   numero_facture, reference_affaire, pays_destination,
     *   incoterm, incoterm_lieu, transitaire_nom, transitaire_contact,
     *   poids, cout_transitaire
     *
     * Étape 1 - MAD Fournisseur:
     *   mad_date_prevue, mad_date_reelle,
     *   mad_docs_recus, mad_photos_recues, mad_coc_recu,
     *   mad_date_docs_recus, mad_observations, mad_complete
     *
     * Étape 2 - Facturation:
     *   fact_emise, fact_date, fact_numero, fact_paiement_recu,
     *   fact_date_paiement, fact_montant, fact_devise, fact_observations
     *
     * Étape 3 - Transitaire:
     *   trans_communique, trans_date_reception, trans_date_instructions,
     *   trans_date_enlevement, trans_observations
     *
     * Étape 4 - Livraison:
     *   liv_date_prevue, liv_date_reelle, liv_mode_transport,
     *   liv_awb, liv_applicable, liv_observations
     *
     * Étape 5 - Clôture:
     *   clot_pod_recue, clot_date_pod, clot_reference,
     *   clot_source, clot_observations
     */
    public function collection(Collection $rows): void
    {
        // Cache lookups to avoid N+1 queries
        $clients      = Client::pluck('id', 'nom');
        $fournisseurs = Fournisseur::pluck('id', 'nom');
        $users        = User::pluck('id', 'initiales');

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            try {
                DB::transaction(function () use ($row, $rowNum, $clients, $fournisseurs, $users) {
                    $this->importRow($row, $rowNum, $clients, $fournisseurs, $users);
                });
            } catch (\Throwable $e) {
                $ref = $row['reference'] ?? "ligne {$rowNum}";
                $this->addError("Dossier {$ref} : {$e->getMessage()}");
                $this->incrementSkipped();
            }
        }
    }

    private function importRow(
        Collection $row,
        int $rowNum,
        Collection $clients,
        Collection $fournisseurs,
        Collection $users,
    ): void {
        // ── Resolve FK: client ──────────────────────────────────────
        $clientNom = trim((string) ($row['client'] ?? ''));
        if (empty($clientNom)) {
            $this->addError("Ligne {$rowNum} : colonne 'client' manquante — ignorée.");
            $this->incrementSkipped();
            return;
        }

        $clientId = $clients[$clientNom] ?? null;
        if (!$clientId) {
            // Auto-create unknown clients
            $client = Client::create(['nom' => $clientNom]);
            $clients[$clientNom] = $client->id;
            $clientId = $client->id;
        }

        // ── Resolve FK: fournisseur ─────────────────────────────────
        $fournisseurNom = trim((string) ($row['fournisseur'] ?? ''));
        // APRÈS — simplement laisser null
        $fournisseurId = null;
        if (!empty($fournisseurNom)) {
            $fournisseurId = $fournisseurs[$fournisseurNom] ?? null;
            if (!$fournisseurId) {
                $f = Fournisseur::create(['nom' => $fournisseurNom]);
                $fournisseurs[$fournisseurNom] = $f->id;
                $fournisseurId = $f->id;
            }
        }

        // ── Resolve FK: user (responsable) ──────────────────────────
        $initiales = strtoupper(trim((string) ($row['responsable'] ?? '')));
        $userId = $users[$initiales] ?? $users->first() ?? 1;

        // ── Incoterm normalization ───────────────────────────────────
        $incotermRaw = strtoupper(trim((string) ($row['incoterm'] ?? 'FCA_USINE')));
        $incoterm = match(true) {
            str_contains($incotermRaw, 'USINE')       => 'FCA_USINE',
            str_contains($incotermRaw, 'TRANS')       => 'FCA_TRANSITAIRE',
            $incotermRaw === 'FCA'                    => 'FCA_USINE',
            $incotermRaw === 'CPT'                    => 'CPT',
            $incotermRaw === 'CFR'                    => 'CFR',
            $incotermRaw === 'EXW'                    => 'EXW',
            default                                   => 'AUTRES',
        };

        // ── Reference ───────────────────────────────────────────────
        $reference = trim((string) ($row['reference'] ?? ''));
        if (empty($reference)) {
            $reference = Dossier::genererReference();
        }

        // ── Dossier ─────────────────────────────────────────────────
        $dossier = Dossier::updateOrCreate(
            ['reference' => $reference],
            [
                'user_id'          => $userId,
                'client_id'        => $clientId,
                'fournisseur_id'   => $fournisseurId,
                'numero_facture'   => trim((string) ($row['numero_facture'] ?? '')) ?: null,
                'reference_affaire'=> trim((string) ($row['reference_affaire'] ?? $row['affaire'] ?? '')) ?: null,
                'pays_destination' => trim((string) ($row['pays_destination'] ?? $row['pays'] ?? '')) ?: null,
                'incoterm'         => $incoterm,
                'incoterm_lieu'    => trim((string) ($row['incoterm_lieu'] ?? '')) ?: null,
                'transitaire_nom'  => trim((string) ($row['transitaire_nom'] ?? $row['transitaire'] ?? '')) ?: null,
                'transitaire_contact' => trim((string) ($row['transitaire_contact'] ?? '')) ?: null,
                'poids'            => $this->cleanDecimal($row['poids'] ?? null),
                'cout_transitaire' => $this->cleanDecimal($row['cout_transitaire'] ?? null),
            ]
        );

        // ── Étape 1 ─────────────────────────────────────────────────
        $madComplete = $this->cleanBool($row['mad_complete'] ?? false);
        $dossier->etapeMadFournisseur()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'date_mad_prevue'  => $this->cleanDate((string) ($row['mad_date_prevue'] ?? '')),
                'date_mad_reelle'  => $this->cleanDate((string) ($row['mad_date_reelle'] ?? '')),
                'docs_recus'       => $this->cleanBool($row['mad_docs_recus'] ?? false),
                'photos_recues'    => $this->cleanBool($row['mad_photos_recues'] ?? false),
                'coc_recu'         => $this->cleanBool($row['mad_coc_recu'] ?? false),
                'date_docs_recus'  => $this->cleanDate((string) ($row['mad_date_docs_recus'] ?? '')),
                'observations'     => trim((string) ($row['mad_observations'] ?? '')) ?: null,
                'complete'         => $madComplete,
            ]
        );

        // ── Étape 2 ─────────────────────────────────────────────────
        $devise = strtoupper(trim((string) ($row['fact_devise'] ?? 'EUR')));
        $devise = in_array($devise, ['EUR', 'USD', 'XAF', 'GBP']) ? $devise : 'EUR';

        $dossier->etapeFacturation()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'facture_emise'          => $this->cleanBool($row['fact_emise'] ?? false),
                'date_facturation'       => $this->cleanDate((string) ($row['fact_date'] ?? '')),
                'numero_facture_interne' => trim((string) ($row['fact_numero'] ?? '')) ?: null,
                'paiement_recu'          => $this->cleanBool($row['fact_paiement_recu'] ?? false),
                'date_paiement'          => $this->cleanDate((string) ($row['fact_date_paiement'] ?? '')),
                'montant'                => $this->cleanDecimal($row['fact_montant'] ?? null),
                'devise'                 => $devise,
                'observations'           => trim((string) ($row['fact_observations'] ?? '')) ?: null,
                'complete'               => $this->cleanBool($row['fact_complete'] ?? false),
            ]
        );

        // ── Étape 3 ─────────────────────────────────────────────────
        $madReelle  = $this->cleanDate((string) ($row['mad_date_reelle'] ?? ''));
        $enlevement = $this->cleanDate((string) ($row['trans_date_enlevement'] ?? ''));
        $tempsTraitement = null;
        if ($madReelle && $enlevement) {
            $tempsTraitement = now()->parse($madReelle)->diffInDays(now()->parse($enlevement));
        }

        $dossier->etapeTransitaire()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'transitaire_communique'           => $this->cleanBool($row['trans_communique'] ?? false),
                'date_reception_infos_transitaire' => $this->cleanDate((string) ($row['trans_date_reception'] ?? '')),
                'date_instructions_envoyees'       => $this->cleanDate((string) ($row['trans_date_instructions'] ?? '')),
                'date_enlevement'                  => $enlevement,
                'temps_traitement_jours'           => $tempsTraitement,
                'observations'                     => trim((string) ($row['trans_observations'] ?? '')) ?: null,
                'complete'                         => $this->cleanBool($row['trans_complete'] ?? false),
            ]
        );

        // ── Étape 4 ─────────────────────────────────────────────────
        $dossier->etapeLivraison()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'date_livraison_prevue' => $this->cleanDate((string) ($row['liv_date_prevue'] ?? '')),
                'date_livraison_reelle' => $this->cleanDate((string) ($row['liv_date_reelle'] ?? '')),
                'mode_transport'        => trim((string) ($row['liv_mode_transport'] ?? '')) ?: null,
                'awb_bl_numero'         => trim((string) ($row['liv_awb'] ?? '')) ?: null,
                'applicable'            => $this->cleanBool($row['liv_applicable'] ?? true),
                'observations'          => trim((string) ($row['liv_observations'] ?? '')) ?: null,
                'complete'              => $this->cleanBool($row['liv_complete'] ?? false),
            ]
        );

        // ── Étape 5 ─────────────────────────────────────────────────
        $dossier->etapeCloture()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'pod_recue'     => $this->cleanBool($row['clot_pod_recue'] ?? false),
                'date_pod'      => $this->cleanDate((string) ($row['clot_date_pod'] ?? '')),
                'pod_reference' => trim((string) ($row['clot_reference'] ?? '')) ?: null,
                'pod_source'    => trim((string) ($row['clot_source'] ?? '')) ?: null,
                'observations'  => trim((string) ($row['clot_observations'] ?? '')) ?: null,
                'complete'      => $this->cleanBool($row['clot_complete'] ?? false),
            ]
        );

        // ── Recalculate statut + alertes ─────────────────────────────
        $dossier->refresh();
        $dossier->recalculerStatut();

        $this->incrementImported();
    }
}
