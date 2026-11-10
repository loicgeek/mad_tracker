<?php

namespace App\Console\Commands;

use App\Models\Dossier;
use Illuminate\Console\Command;

/**
 * Les alertes dépendent du temps qui passe (« 3 jours après la date de
 * facturation »…) : sans recalcul quotidien, un dossier que personne ne
 * ré-enregistre ne passerait jamais en alerte.
 */
class RecalculerAlertes extends Command
{
    protected $signature = 'alertes:recalculer';

    protected $description = 'Recalcule les alertes de tous les dossiers (délais écoulés depuis la dernière saisie).';

    public function handle(): int
    {
        $total = 0;

        Dossier::query()
            ->with(['etapeMadFournisseur', 'etapeFacturation', 'etapeTransitaire', 'etapeLivraison', 'etapeCloture'])
            ->chunkById(200, function ($dossiers) use (&$total) {
                foreach ($dossiers as $dossier) {
                    $dossier->refreshAlertes();
                    $total++;
                }
            });

        $enAlerte = Dossier::withAlertes()->count();
        $this->info("{$total} dossier(s) recalculé(s), {$enAlerte} en alerte.");

        return self::SUCCESS;
    }
}
