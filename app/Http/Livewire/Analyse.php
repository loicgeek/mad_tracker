<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Dossier;
use App\Models\Client;
use App\Models\Fournisseur;
use Illuminate\Support\Facades\DB;

class Analyse extends Component
{
    public string $periode = '12'; // mois
    public string $groupBy = 'fournisseur';

    public array $statsGlobales = [];
    public array $parFournisseur = [];
    public array $parClient = [];
    public array $parIncoterm = [];
    public array $parResponsable = [];

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
    }

    public function render()
    {
        return view('livewire.analyse')
            ->layout('layouts.app', ['title' => 'Analyses']);
    }
}
