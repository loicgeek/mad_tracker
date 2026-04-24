<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Dossier;
use App\Models\Client;
use App\Models\Fournisseur;
use Illuminate\Support\Facades\DB;

class Analyse extends Component
{
    public string $periode  = '12'; // mois
    public string $groupBy  = 'fournisseur';
    public string $viewMode = 'chart'; // table | chart

    public array $statsGlobales = [];
    public array $parFournisseur = [];
    public array $parClient = [];
    public array $parIncoterm = [];
    public array $parResponsable = [];
    public array $parTransporteur = [];
    public array $parType = [];
    public array $delaiParIncoterm = [];
    public array $delaiValidationDocs = [];
    public array $echeancierPaiements = [];

    public function mount(): void { $this->loadData(); }
    public function updatedPeriode(): void { $this->loadData(); }

    private function loadData(): void
    {
        $since = now()->subMonths((int) $this->periode);

        $base = Dossier::query()->where('created_at', '>=', $since);

        // Stats globales
        $this->statsGlobales = [
            'total'           => (clone $base)->count(),
            'finalises'       => (clone $base)->where('statut', 'finalise')->count(),
            'taux_finalisation'=> (clone $base)->count() > 0
                ? round((clone $base)->where('statut', 'finalise')->count() / (clone $base)->count() * 100, 1)
                : 0,
            'moy_traitement'  => round(
                DB::table('etape_transitaires')
                    ->join('dossiers', 'dossiers.id', '=', 'etape_transitaires.dossier_id')
                    ->where('dossiers.created_at', '>=', $since)
                    ->whereNotNull('temps_traitement_jours')
                    ->avg('temps_traitement_jours') ?? 0, 1
            ),
            'total_pod'       => (clone $base)->where('alerte_pod_manquante', false)
                                    ->where('statut', 'finalise')->count(),
            'ecart_moyen_mad' => round(
                DB::table('etape_mad_fournisseurs')
                    ->join('dossiers','dossiers.id','=','etape_mad_fournisseurs.dossier_id')
                    ->where('dossiers.created_at','>=',$since)
                    ->whereNotNull('date_mad_reelle')
                    ->whereNotNull('date_mad_prevue')
                    ->selectRaw('AVG(DATEDIFF(date_mad_reelle, date_mad_prevue)) as ecart')
                    ->value('ecart') ?? 0, 1
            ),
        ];

        // Par fournisseur
        $this->parFournisseur = DB::table('dossiers')
            ->join('fournisseurs', 'fournisseurs.id', '=', 'dossiers.fournisseur_id')
            ->leftJoin('etape_transitaires', 'etape_transitaires.dossier_id', '=', 'dossiers.id')
            ->where('dossiers.created_at', '>=', $since)
            ->groupBy('fournisseurs.id', 'fournisseurs.nom')
            ->select(
                'fournisseurs.nom',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN dossiers.statut = "finalise" THEN 1 ELSE 0 END) as finalises'),
                DB::raw('AVG(etape_transitaires.temps_traitement_jours) as moy_traitement'),
            )
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->toArray();

        // Par client
        $this->parClient = DB::table('dossiers')
            ->join('clients', 'clients.id', '=', 'dossiers.client_id')
            ->where('dossiers.created_at', '>=', $since)
            ->groupBy('clients.id', 'clients.nom')
            ->select(
                'clients.nom',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN dossiers.statut = "finalise" THEN 1 ELSE 0 END) as finalises'),
            )
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->toArray();

        // Par incoterm
        $this->parIncoterm = DB::table('dossiers')
            ->where('created_at', '>=', $since)
            ->groupBy('incoterm')
            ->select(
                'incoterm',
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG((SELECT temps_traitement_jours FROM etape_transitaires WHERE dossier_id = dossiers.id)) as moy_traitement'),
            )
            ->orderByDesc('total')
            ->get()
            ->toArray();

        // Par responsable
        $this->parResponsable = DB::table('dossiers')
            ->join('users', 'users.id', '=', 'dossiers.user_id')
            ->where('dossiers.created_at', '>=', $since)
            ->groupBy('users.id', 'users.nom', 'users.prenom', 'users.initiales')
            ->select(
                DB::raw('CONCAT(users.prenom, " ", users.nom) as nom'),
                'users.initiales',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN dossiers.statut = "finalise" THEN 1 ELSE 0 END) as finalises'),
                DB::raw('SUM(CASE WHEN dossiers.alerte_retard_mad = 1 OR dossiers.alerte_livraison_depassee = 1 THEN 1 ELSE 0 END) as alertes'),
            )
            ->orderByDesc('total')
            ->get()
            ->toArray();

        // Par transporteur
        $this->parTransporteur = DB::table('dossiers')
            ->join('transporteurs', 'transporteurs.id', '=', 'dossiers.transporteur_id')
            ->leftJoin('etape_livraisons', 'etape_livraisons.dossier_id', '=', 'dossiers.id')
            ->where('dossiers.created_at', '>=', $since)
            ->whereNull('dossiers.deleted_at')
            ->groupBy('transporteurs.id', 'transporteurs.nom')
            ->select(
                'transporteurs.nom',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN etape_livraisons.date_livraison_reelle IS NOT NULL THEN 1 ELSE 0 END) as total_livres'),
                DB::raw('SUM(CASE WHEN etape_livraisons.ecart_livraison_jours <= 0 AND etape_livraisons.date_livraison_reelle IS NOT NULL THEN 1 ELSE 0 END) as livres_a_temps'),
                DB::raw('AVG(dossiers.cout_transitaire) as moy_cout_prevu'),
                DB::raw('AVG(dossiers.cout_reel) as moy_cout_reel'),
            )
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) {
                $r->pct_a_temps = $r->total_livres > 0
                    ? round($r->livres_a_temps / $r->total_livres * 100, 1)
                    : null;
                $r->ecart_pct = ($r->moy_cout_prevu > 0 && $r->moy_cout_reel !== null)
                    ? round(($r->moy_cout_reel - $r->moy_cout_prevu) / $r->moy_cout_prevu * 100, 1)
                    : null;
                return $r;
            })
            ->toArray();

        // Par type de commande
        $this->parType = DB::table('dossiers')
            ->where('dossiers.created_at', '>=', $since)
            ->whereNull('dossiers.deleted_at')
            ->whereIn('type_commande', ['standard', 'projet'])
            ->leftJoin('etape_livraisons', 'etape_livraisons.dossier_id', '=', 'dossiers.id')
            ->groupBy('dossiers.type_commande')
            ->select(
                'dossiers.type_commande',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN dossiers.statut = "finalise" THEN 1 ELSE 0 END) as finalises'),
                DB::raw('AVG(etape_livraisons.ecart_livraison_jours) as delai_moyen'),
            )
            ->get()
            ->map(function ($r) {
                $r->taux = $r->total > 0 ? round($r->finalises / $r->total * 100, 1) : 0;
                $r->delai_moyen = $r->delai_moyen !== null ? round($r->delai_moyen, 1) : null;
                return $r;
            })
            ->toArray();

        // Délais de validation des documents (écart MAD réelle → date validation document)
        $this->delaiValidationDocs = DB::table('dossiers')
            ->join('etape_mad_fournisseurs', 'etape_mad_fournisseurs.dossier_id', '=', 'dossiers.id')
            ->where('dossiers.created_at', '>=', $since)
            ->whereNull('dossiers.deleted_at')
            ->whereNotNull('etape_mad_fournisseurs.date_mad_reelle')
            ->whereNotNull('etape_mad_fournisseurs.date_validation_document')
            ->select(
                'dossiers.reference',
                'dossiers.type_commande',
                DB::raw('etape_mad_fournisseurs.date_mad_reelle as mad_reelle'),
                DB::raw('etape_mad_fournisseurs.date_validation_document as date_validation'),
                DB::raw('DATEDIFF(etape_mad_fournisseurs.date_validation_document, etape_mad_fournisseurs.date_mad_reelle) as ecart_jours'),
            )
            ->orderByDesc('dossiers.created_at')
            ->limit(50)
            ->get()
            ->toArray();

        // Analyse des échéanciers de paiements (écart échéance → date paiement)
        $this->echeancierPaiements = DB::table('dossiers')
            ->join('etape_facturations', 'etape_facturations.dossier_id', '=', 'dossiers.id')
            ->join('clients', 'clients.id', '=', 'dossiers.client_id')
            ->where('dossiers.created_at', '>=', $since)
            ->whereNull('dossiers.deleted_at')
            ->whereNotNull('etape_facturations.date_echeance_facture')
            ->select(
                'dossiers.reference',
                'clients.nom as client',
                DB::raw('etape_facturations.numero_facture'),
                DB::raw('etape_facturations.montant'),
                DB::raw('etape_facturations.devise'),
                DB::raw('etape_facturations.date_facturation'),
                DB::raw('etape_facturations.date_echeance_facture as date_echeance'),
                DB::raw('etape_facturations.date_paiement'),
                DB::raw('etape_facturations.paiement_recu'),
                DB::raw('CASE WHEN etape_facturations.date_paiement IS NOT NULL
                         THEN DATEDIFF(etape_facturations.date_paiement, etape_facturations.date_echeance_facture)
                         ELSE DATEDIFF(CURRENT_DATE, etape_facturations.date_echeance_facture)
                         END as ecart_echeance_jours'),
            )
            ->orderBy('etape_facturations.date_echeance_facture')
            ->get()
            ->toArray();

        // Délai moyen par incoterm
        $this->delaiParIncoterm = DB::table('dossiers')
            ->join('etape_livraisons', 'etape_livraisons.dossier_id', '=', 'dossiers.id')
            ->where('dossiers.created_at', '>=', $since)
            ->whereNull('dossiers.deleted_at')
            ->whereNotNull('etape_livraisons.ecart_livraison_jours')
            ->groupBy('dossiers.incoterm')
            ->select(
                'dossiers.incoterm',
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(etape_livraisons.ecart_livraison_jours) as delai_moyen'),
            )
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => (object)[
                'incoterm'    => $r->incoterm,
                'total'       => $r->total,
                'delai_moyen' => round($r->delai_moyen, 1),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.analyse')
            ->layout('layouts.app', ['title' => 'Analyses']);
    }
}
