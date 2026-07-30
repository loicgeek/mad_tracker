<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\Fournisseur;
use App\Models\Observation;
use App\Models\Transporteur;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reprise ponctuelle des données historiques :
 *   - le référentiel "Liste des transitaires, clients et fournisseurs 2026.xlsx"
 *     (3 listes CLIENTS / FOURNISSEURS / TRANSITAIRE côte à côte, colonnes A/C/E)
 *   - le fichier de suivi "MAD 2026.xlsx" (onglet Feuil3, en-têtes MAJUSCULES ad hoc,
 *     format différent du template d'import standard — mapping colonne→champ
 *     déterminé par inspection des valeurs réelles, voir le plan associé)
 *
 * One-off : ne remplace pas le bouton d'import standard (/import).
 */
class ImporterDonneesLegacy extends Command
{
    protected $signature = 'mad:import-legacy
        {--referentiel= : Chemin vers le fichier "Liste des transitaires, clients et fournisseurs"}
        {--dossiers= : Chemin vers le fichier "MAD 2026.xlsx"}
        {--dry-run : Simule l\'import sans écrire en base (transaction annulée à la fin)}';

    protected $description = 'Reprise ponctuelle des données historiques (référentiel + dossiers MAD 2026).';

    private bool $dryRun = false;
    private array $comptesPlaceholderCrees = [];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $referentielPath = $this->option('referentiel');
        $dossiersPath    = $this->option('dossiers');

        if (! $referentielPath && ! $dossiersPath) {
            $this->error('Fournir au moins --referentiel=... ou --dossiers=...');
            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            if ($referentielPath) {
                $this->importerReferentiel($referentielPath);
            }

            if ($dossiersPath) {
                $this->importerDossiers($dossiersPath);
            }

            if (! empty($this->comptesPlaceholderCrees)) {
                $this->warn('Comptes utilisateur placeholder créés pour ces initiales (à compléter/rattacher manuellement) : '
                    . implode(', ', array_unique($this->comptesPlaceholderCrees)));
            }

            if ($this->dryRun) {
                DB::rollBack();
                $this->warn('--dry-run : aucune modification n\'a été enregistrée en base.');
            } else {
                DB::commit();
                $this->info('Import terminé et enregistré en base.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Échec de l'import, aucune modification enregistrée : {$e->getMessage()}");
            throw $e;
        }

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────
    // Référentiel : Clients / Fournisseurs / Transitaires
    // ─────────────────────────────────────────────────────────────

    private function importerReferentiel(string $path): void
    {
        $this->info("Import du référentiel : {$path}");

        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows  = $sheet->toArray(null, true, false, false);

        $counts = ['clients' => 0, 'fournisseurs' => 0, 'transitaires' => 0];

        foreach (array_slice($rows, 1) as $row) {
            $client      = $this->clean($row[0] ?? null);
            $fournisseur = $this->clean($row[2] ?? null);
            $transitaire = $this->clean($row[4] ?? null);

            if ($client && ! Client::where('nom', $client)->exists()) {
                Client::create(['nom' => $client]);
                $counts['clients']++;
            }
            if ($fournisseur && ! Fournisseur::where('nom', $fournisseur)->exists()) {
                Fournisseur::create(['nom' => $fournisseur]);
                $counts['fournisseurs']++;
            }
            if ($transitaire && ! Transporteur::where('nom', $transitaire)->exists()) {
                Transporteur::create(['nom' => $transitaire]);
                $counts['transitaires']++;
            }
        }

        $this->info("Référentiel : {$counts['clients']} client(s), {$counts['fournisseurs']} fournisseur(s), {$counts['transitaires']} transitaire(s) créés.");
    }

    // ─────────────────────────────────────────────────────────────
    // Dossiers (MAD 2026.xlsx — Feuil3)
    // ─────────────────────────────────────────────────────────────

    private function importerDossiers(string $path): void
    {
        $this->info("Import des dossiers : {$path}");

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Feuil3') ?? $spreadsheet->getActiveSheet();
        $rows  = $sheet->toArray(null, true, false, false);

        $crees = 0;
        $maj   = 0;

        foreach (array_slice($rows, 1) as $i => $row) {
            $numFacture = $this->clean($row[1] ?? null);
            $clientNom  = $this->clean($row[3] ?? null);

            if (! $clientNom) {
                $this->warn('Ligne ' . ($i + 2) . ' : colonne CLIENT vide — ignorée.');
                continue;
            }

            $client = Client::firstOrCreate(
                ['nom' => $clientNom],
                ['pays' => $this->clean($row[4] ?? null)]
            );

            $fournisseurNom = $this->clean($row[10] ?? null);
            $fournisseur = $fournisseurNom
                ? Fournisseur::firstOrCreate(['nom' => $fournisseurNom])
                : null;

            $user = $this->resoudreResponsable($this->clean($row[0] ?? null));

            $categorie = strtoupper((string) ($this->clean($row[6] ?? null) ?? ''));
            $incoterm = match (true) {
                str_contains($categorie, 'USINE')       => 'FCA_USINE',
                str_contains($categorie, 'TRANSITAIRE') => 'FCA_TRANSITAIRE',
                default                                  => 'AUTRES',
            };

            $dateMadFournisseurNote = $this->excelDate($row[11] ?? null);
            $observationsMad = trim(implode(' — ', array_filter([
                $dateMadFournisseurNote ? "Date MAD fournisseur (brute) : {$dateMadFournisseurNote}" : null,
                $this->clean($row[15] ?? null),
                $this->clean($row[22] ?? null),
            ])));

            $dossierData = [
                'user_id'           => $user?->id ?? User::first()?->id,
                'client_id'         => $client->id,
                'fournisseur_id'    => $fournisseur?->id,
                'numero_facture'    => $numFacture,
                'reference_affaire' => $this->clean($row[2] ?? null),
                'pays_destination'  => $this->clean($row[4] ?? null),
                'incoterm'          => $incoterm,
                'incoterm_lieu'     => $this->clean($row[5] ?? null),
                'transitaire_nom'   => $this->clean($row[7] ?? null),
                'poids'             => $this->decimal($row[8] ?? null),
                'cout_transitaire'  => $this->decimal($row[9] ?? null),
            ];

            $dossier = $numFacture
                ? Dossier::withTrashed()->where('numero_facture', $numFacture)->first()
                : null;

            if ($dossier) {
                $dossier->update($dossierData);
                $maj++;
            } else {
                $dossier = Dossier::create(array_merge($dossierData, [
                    'reference' => Dossier::genererReference(),
                ]));
                $crees++;
            }

            $dossier->etapeMadFournisseur()->updateOrCreate(
                ['dossier_id' => $dossier->id],
                [
                    'date_mad_prevue' => $this->excelDate($row[20] ?? null),
                    'date_mad_reelle' => $this->excelDate($row[14] ?? null),
                    'docs_recus'      => $this->clean($row[12] ?? null) !== null,
                    'photos_recues'   => $this->clean($row[13] ?? null) !== null,
                    'coc_recu'        => $this->clean($row[16] ?? null) !== null,
                    'observations'    => $observationsMad ?: null,
                ]
            );

            $enlevement = $this->excelDate($row[17] ?? null);
            $dossier->etapeTransitaire()->updateOrCreate(
                ['dossier_id' => $dossier->id],
                ['date_enlevement' => $enlevement]
            );

            $podDate = $this->excelDate($row[18] ?? null);
            $dossier->etapeCloture()->updateOrCreate(
                ['dossier_id' => $dossier->id],
                [
                    'pod_recue' => $podDate !== null,
                    'date_pod'  => $podDate,
                ]
            );

            $raisonRetard = $this->clean($row[24] ?? null);
            $remarques = trim(implode(' — ', array_filter([
                $this->clean($row[23] ?? null),
                $raisonRetard ? "Raison retard : {$raisonRetard}" : null,
            ])));
            if ($remarques !== '') {
                Observation::create([
                    'dossier_id' => $dossier->id,
                    'user_id'    => $user?->id,
                    'etape'      => 'import',
                    'contenu'    => $remarques,
                    'type'       => 'info',
                ]);
            }

            $dossier->load(['etapeMadFournisseur', 'etapeFacturation', 'etapeTransitaire', 'etapeLivraison', 'etapeCloture']);
            $dossier->recalculerStatut();
        }

        $this->info("Dossiers : {$crees} créé(s), {$maj} mis à jour.");
    }

    private function resoudreResponsable(?string $raw): ?User
    {
        if (! $raw) {
            return null;
        }

        $initiales = strtoupper(trim(explode('/', $raw)[0]));

        $user = User::where('initiales', $initiales)->first();
        if ($user) {
            return $user;
        }

        $user = User::create([
            'nom'       => 'À compléter',
            'prenom'    => $initiales,
            'initiales' => $initiales,
            'email'     => Str::slug($initiales) . '-a-completer@kehitaa.local',
            'password'  => bcrypt(Str::random(32)),
            'role'      => 'gestionnaire',
            'actif'     => false,
        ]);

        $this->comptesPlaceholderCrees[] = $initiales;

        return $user;
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function clean(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim(str_replace("\xc2\xa0", ' ', (string) $v));
        return $s === '' ? null : $s;
    }

    private function decimal(mixed $v): ?float
    {
        $s = $this->clean($v);
        return $s !== null && is_numeric($s) ? (float) $s : null;
    }

    private function excelDate(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        if (is_numeric($v) && (int) $v > 1000) {
            try {
                return \Carbon\Carbon::createFromTimestamp(
                    ExcelDate::excelToTimestamp((float) $v)
                )->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $s = trim((string) $v);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $s, $m)) {
            try {
                return \Carbon\Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
