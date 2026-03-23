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

/**
 * Imports dossiers from:
 *   (A) the template produced by DossiersTemplateExport  → prefixed keys: mad_date_prevue, fact_emise …
 *   (B) the real tracking spreadsheet (dossiers-mad-*.xlsx) → French slugged keys: mad_prevue, facture …
 *
 * WithHeadingRow slugifies headers via Str::slug($h, '_'), so French accents and
 * special chars are stripped.  For example:
 *   "MAD Prévue"        → mad_prevue       (template expects: mad_date_prevue)
 *   "Facturé"           → facture          (template expects: fact_emise)
 *   "Docs Reçus"        → docs_recus       (template expects: mad_docs_recus)
 *   "N° Facture"        → n_facture        (template expects: numero_facture)
 *   "Poids (kg)"        → poids_kg         (template expects: poids)
 *
 * The col() helper checks both key variants so either file just works
 * without maintaining two separate importers.
 *
 * Full key-mapping table:
 *   General      : numero_facture ↔ n_facture
 *                  reference_affaire ↔ affaire
 *                  pays_destination ↔ pays
 *                  incoterm_lieu ↔ lieu_incoterm
 *                  transitaire_nom ↔ transitaire
 *                  poids ↔ poids_kg
 *   MAD          : mad_date_prevue ↔ mad_prevue
 *                  mad_date_reelle ↔ mad_reelle
 *                  mad_docs_recus ↔ docs_recus
 *                  mad_photos_recues ↔ photos_recues
 *                  mad_coc_recu ↔ coc_recu
 *                  mad_observations ↔ obs_mad_fourn
 *   Facturation  : fact_emise ↔ facture
 *                  fact_date ↔ date_facturation
 *                  fact_numero ↔ n_facture_interne
 *                  fact_paiement_recu ↔ paiement_recu
 *                  fact_date_paiement ↔ date_paiement
 *                  fact_montant ↔ montant
 *                  fact_devise ↔ devise
 *                  fact_observations ↔ obs_facturation
 *   Transitaire  : trans_communique ↔ transitaire_communique
 *                  trans_date_reception ↔ date_reception_infos
 *                  trans_date_instructions ↔ date_instructions
 *                  trans_date_enlevement ↔ date_enlevement
 *                  trans_observations ↔ obs_transitaire
 *   Livraison    : liv_date_prevue ↔ livraison_prevue
 *                  liv_date_reelle ↔ livraison_reelle
 *                  liv_mode_transport ↔ mode_transport
 *                  liv_awb ↔ awb_bl
 *                  liv_observations ↔ obs_livraison
 *   Clôture      : clot_pod_recue ↔ pod_recue
 *                  clot_date_pod ↔ date_pod
 *                  clot_reference ↔ ref_pod
 *                  clot_source ↔ source_pod
 *                  clot_observations ↔ obs_cloture
 */
class DossiersImport implements ToCollection, WithHeadingRow, SkipsOnError, WithChunkReading
{
    use WithImportTracking, SkipsErrors;

    public function chunkSize(): int { return 100; }

    public function collection(Collection $rows): void
    {
        // Cache FK lookups once per chunk to avoid N+1 queries
        $clients      = Client::pluck('id', 'nom');
        $fournisseurs = Fournisseur::pluck('id', 'nom');
        $users        = User::pluck('id', 'initiales');

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            try {
                DB::transaction(function () use ($row, $rowNum, &$clients, &$fournisseurs, $users) {
                    $this->importRow($row, $rowNum, $clients, $fournisseurs, $users);
                });
            } catch (\Throwable $e) {
                $ref = $this->str($row['reference'] ?? null) ?? "ligne {$rowNum}";
                $this->addError("Dossier {$ref} : {$e->getMessage()}");
                $this->incrementSkipped();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Row handler
    // ─────────────────────────────────────────────────────────────────

    private function importRow(
        Collection $row,
        int $rowNum,
        Collection &$clients,
        Collection &$fournisseurs,
        Collection $users,
    ): void {
        // ── Client (required) ────────────────────────────────────────
        $clientNom = trim((string) ($this->col($row, 'client') ?? ''));
        if ($clientNom === '') {
            $this->addError("Ligne {$rowNum} : colonne 'client' manquante — ignorée.");
            $this->incrementSkipped();
            return;
        }
        $clientId = $clients[$clientNom] ?? null;
        if (! $clientId) {
            $pays = $this->str($this->col($row, 'pays_destination', 'pays'));
            $c    = Client::create(['nom' => $clientNom, 'pays' => $pays]);
            $clients[$clientNom] = $c->id;
            $clientId = $c->id;
        }

        // ── Fournisseur (optional) ───────────────────────────────────
        $fournisseurNom = trim((string) ($this->col($row, 'fournisseur') ?? ''));
        $fournisseurId  = null;
        if ($fournisseurNom !== '') {
            $fournisseurId = $fournisseurs[$fournisseurNom] ?? null;
            if (! $fournisseurId) {
                $f = Fournisseur::create(['nom' => $fournisseurNom]);
                $fournisseurs[$fournisseurNom] = $f->id;
                $fournisseurId = $f->id;
            }
        }

        // ── User / responsable ───────────────────────────────────────
        $initiales = strtoupper(trim((string) ($this->col($row, 'responsable') ?? '')));
        $userId    = $users[$initiales] ?? ($users->first() ?? 1);

        // ── Incoterm ─────────────────────────────────────────────────
        $incotermRaw = strtoupper(trim((string) ($this->col($row, 'incoterm') ?? '')));
        $incoterm = match (true) {
            str_contains($incotermRaw, 'USINE')  => 'FCA_USINE',
            str_contains($incotermRaw, 'TRANS')  => 'FCA_TRANSITAIRE',
            $incotermRaw === 'FCA'               => 'FCA_USINE',
            $incotermRaw === 'CPT'               => 'CPT',
            $incotermRaw === 'CFR'               => 'CFR',
            $incotermRaw === 'EXW'               => 'EXW',
            default                              => 'AUTRES',
        };

        // ── Reference ────────────────────────────────────────────────
        $reference = trim((string) ($this->col($row, 'reference') ?? ''));
        if ($reference === '') {
            $reference = Dossier::genererReference();
        }

        // ── Dossier upsert ───────────────────────────────────────────
        $dossier = Dossier::updateOrCreate(
            ['reference' => $reference],
            [
                'user_id'             => $userId,
                'client_id'           => $clientId,
                'fournisseur_id'      => $fournisseurId,
                'numero_facture'      => $this->str($this->col($row, 'numero_facture', 'n_facture')),
                'reference_affaire'   => $this->str($this->col($row, 'reference_affaire', 'affaire')),
                'pays_destination'    => $this->str($this->col($row, 'pays_destination', 'pays')),
                'incoterm'            => $incoterm,
                'incoterm_lieu'       => $this->str($this->col($row, 'incoterm_lieu', 'lieu_incoterm')),
                'transitaire_nom'     => $this->str($this->col($row, 'transitaire_nom', 'transitaire')),
                'transitaire_contact' => $this->str($this->col($row, 'transitaire_contact')),
                'poids'               => $this->decimal($this->col($row, 'poids', 'poids_kg')),
                'cout_transitaire'    => $this->decimal($this->col($row, 'cout_transitaire')),
            ]
        );

        // ── Étape 1 — MAD Fournisseur ────────────────────────────────
        $dossier->etapeMadFournisseur()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'date_mad_prevue'  => $this->date($this->col($row, 'mad_date_prevue', 'mad_prevue')),
                'date_mad_reelle'  => $this->date($this->col($row, 'mad_date_reelle', 'mad_reelle')),
                'docs_recus'       => $this->bool($this->col($row, 'mad_docs_recus', 'docs_recus')),
                'photos_recues'    => $this->bool($this->col($row, 'mad_photos_recues', 'photos_recues')),
                'coc_recu'         => $this->bool($this->col($row, 'mad_coc_recu', 'coc_recu')),
                'date_docs_recus'  => $this->date($this->col($row, 'mad_date_docs_recus')),
                'observations'     => $this->str($this->col($row, 'mad_observations', 'obs_mad_fourn')),
                'complete'         => $this->bool($this->col($row, 'mad_complete')),
            ]
        );

        // ── Étape 2 — Facturation ────────────────────────────────────
        $devise = strtoupper(trim((string) ($this->col($row, 'fact_devise', 'devise') ?? 'EUR')));
        $devise = in_array($devise, ['EUR', 'USD', 'XAF', 'GBP']) ? $devise : 'EUR';

        $dossier->etapeFacturation()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'facture_emise'          => $this->bool($this->col($row, 'fact_emise', 'facture')),
                'date_facturation'       => $this->date($this->col($row, 'fact_date', 'date_facturation')),
                'numero_facture_interne' => $this->str($this->col($row, 'fact_numero', 'n_facture_interne')),
                'paiement_recu'          => $this->bool($this->col($row, 'fact_paiement_recu', 'paiement_recu')),
                'date_paiement'          => $this->date($this->col($row, 'fact_date_paiement', 'date_paiement')),
                'montant'                => $this->decimal($this->col($row, 'fact_montant', 'montant')),
                'devise'                 => $devise,
                'observations'           => $this->str($this->col($row, 'fact_observations', 'obs_facturation')),
                'complete'               => $this->bool($this->col($row, 'fact_complete')),
            ]
        );

        // ── Étape 3 — Transitaire ────────────────────────────────────
        $madReelle  = $this->date($this->col($row, 'mad_date_reelle', 'mad_reelle'));
        $enlevement = $this->date($this->col($row, 'trans_date_enlevement', 'date_enlevement'));
        $tempsTraitement = ($madReelle && $enlevement)
            ? now()->parse($madReelle)->diffInDays(now()->parse($enlevement))
            : null;

        $dossier->etapeTransitaire()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'transitaire_communique'           => $this->bool($this->col($row, 'trans_communique', 'transitaire_communique')),
                'date_reception_infos_transitaire' => $this->date($this->col($row, 'trans_date_reception', 'date_reception_infos')),
                'date_instructions_envoyees'       => $this->date($this->col($row, 'trans_date_instructions', 'date_instructions')),
                'date_enlevement'                  => $enlevement,
                'temps_traitement_jours'           => $tempsTraitement,
                'observations'                     => $this->str($this->col($row, 'trans_observations', 'obs_transitaire')),
                'complete'                         => $this->bool($this->col($row, 'trans_complete')),
            ]
        );

        // ── Étape 4 — Livraison ──────────────────────────────────────
        $dossier->etapeLivraison()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'date_livraison_prevue' => $this->date($this->col($row, 'liv_date_prevue', 'livraison_prevue')),
                'date_livraison_reelle' => $this->date($this->col($row, 'liv_date_reelle', 'livraison_reelle')),
                'mode_transport'        => $this->str($this->col($row, 'liv_mode_transport', 'mode_transport')),
                'awb_bl_numero'         => $this->str($this->col($row, 'liv_awb', 'awb_bl')),
                'applicable'            => $this->boolDefault($this->col($row, 'liv_applicable'), true),
                'observations'          => $this->str($this->col($row, 'liv_observations', 'obs_livraison')),
                'complete'              => $this->bool($this->col($row, 'liv_complete')),
            ]
        );

        // ── Étape 5 — Clôture ────────────────────────────────────────
        $dossier->etapeCloture()->updateOrCreate(
            ['dossier_id' => $dossier->id],
            [
                'pod_recue'     => $this->bool($this->col($row, 'clot_pod_recue', 'pod_recue')),
                'date_pod'      => $this->date($this->col($row, 'clot_date_pod', 'date_pod')),
                'pod_reference' => $this->str($this->col($row, 'clot_reference', 'ref_pod')),
                'pod_source'    => $this->str($this->col($row, 'clot_source', 'source_pod')),
                'observations'  => $this->str($this->col($row, 'clot_observations', 'obs_cloture')),
                'complete'      => $this->bool($this->col($row, 'clot_complete')),
            ]
        );

        // ── Recompute statut + alertes ───────────────────────────────
        $dossier->load([
            'etapeMadFournisseur', 'etapeFacturation',
            'etapeTransitaire', 'etapeLivraison', 'etapeCloture',
        ]);
        $dossier->recalculerStatut();

        $this->incrementImported();
    }

    // ─────────────────────────────────────────────────────────────────
    // col() — dual-key lookup
    // ─────────────────────────────────────────────────────────────────

    /**
     * Return the first non-null, non-empty value found in $row for any of
     * the given keys, checked in order.
     *
     * Pass the template key first, the real-file slug second:
     *   $this->col($row, 'mad_date_prevue', 'mad_prevue')
     *
     * This lets one import class handle both file formats transparently.
     */
    private function col(Collection $row, string ...$keys): mixed
    {
        foreach ($keys as $key) {
            $v = $row[$key] ?? null;
            if ($v !== null && $v !== '') {
                return $v;
            }
        }
        return null;
    }

    // ─────────────────────────────────────────────────────────────────
    // Value normalisation helpers
    // ─────────────────────────────────────────────────────────────────

    private function str(mixed $v): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }

    private function bool(mixed $v): bool
    {
        if ($v === null || $v === '') return false;
        return in_array(strtolower(trim((string) $v)), ['oui', '1', 'true', 'yes', 'y'], true);
    }

    private function boolDefault(mixed $v, bool $default): bool
    {
        if ($v === null || $v === '') return $default;
        return $this->bool($v);
    }

    /**
     * Parse dates from:
     *   dd/mm/yyyy  — real file format
     *   yyyy-mm-dd  — ISO / template format
     *   Excel serial — numeric integer (days since 1900-01-01)
     */
    private function date(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;

        if (is_numeric($v) && (int) $v > 1000) {
            try {
                return \Carbon\Carbon::createFromTimestamp(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float) $v)
                )->format('Y-m-d');
            } catch (\Throwable) {}
        }

        $s = trim((string) $v);
        if ($s === '') return null;

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $s, $m)) {
            try {
                return \Carbon\Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1])->format('Y-m-d');
            } catch (\Throwable) {}
        }

        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $s)) return $s;

        try {
            return \Carbon\Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function decimal(mixed $v): ?float
    {
        if ($v === null || $v === '') return null;
        $s = preg_replace('/[^\d.,\-]/', '', str_replace(',', '.', (string) $v));
        return $s !== '' ? (float) $s : null;
    }

    // Compatibility aliases for the WithImportTracking trait or any code
    // that previously called the old cleanXxx() method names.
    protected function cleanBool(mixed $v, bool $default = false): bool { return $v !== null ? $this->bool($v) : $default; }
    protected function cleanDecimal(mixed $v): ?float                   { return $this->decimal($v); }
    protected function cleanDate(string $v): ?string                    { return $this->date($v); }
}