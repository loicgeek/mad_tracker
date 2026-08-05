<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Dossier;
use App\Models\Client;
use App\Models\Fournisseur;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public array $stats = [];
    public array $alertes = [];
    public array $chartStatuts = [];
    public array $chartMensuel = [];
    public array $chartCouts = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadAlertes();
        $this->loadCharts();
    }

    /**
     * gestionnaire/lecteur ne voient que les stats de leurs propres dossiers ;
     * les autres rôles voient l'ensemble.
     */
    private function restreintASesDossiers(): bool
    {
        return in_array(auth()->user()->role, ['gestionnaire', 'lecteur'], true);
    }

    private function dossiersScopes()
    {
        return fn ($q) => $q->when($this->restreintASesDossiers(), fn ($q) => $q->where('user_id', auth()->id()));
    }

    /**
     * Sous-requête dossier_id pour scoper les tables liées (etape_*) qui n'ont
     * pas directement de user_id.
     */
    private function dossierIdsScope($query)
    {
        if (! $this->restreintASesDossiers()) {
            return $query;
        }

        return $query->whereIn('dossier_id', function ($q) {
            $q->select('id')->from('dossiers')->where('user_id', auth()->id());
        });
    }

    private function loadStats(): void
    {
        $livresBase = $this->dossierIdsScope(
            DB::table('etape_livraisons')
                ->whereNotNull('date_livraison_reelle')
                ->whereNotNull('date_livraison_prevue')
        );

        $totalLivres = (clone $livresBase)->count();

        $this->stats = [
            'total'        => Dossier::query()->tap($this->dossiersScopes())->count(),
            'en_cours'     => Dossier::enCours()->tap($this->dossiersScopes())->count(),
            'finalises'    => Dossier::finalises()->tap($this->dossiersScopes())->count(),
            'avec_alertes' => Dossier::withAlertes()->tap($this->dossiersScopes())->count(),
            'clients'      => Client::count(),
            'fournisseurs' => Fournisseur::count(),
            'moy_traitement' => round(
                $this->dossierIdsScope(
                    DB::table('etape_transitaires')->whereNotNull('temps_traitement_jours')
                )->avg('temps_traitement_jours') ?? 0, 1
            ),
            'pod_en_attente' => Dossier::where('alerte_pod_manquante', true)->tap($this->dossiersScopes())->count(),
            // Nouveaux KPIs
            'taux_livraison_temps' => $totalLivres > 0
                ? round((clone $livresBase)->where('ecart_livraison_jours', '<=', 0)->count() / $totalLivres * 100, 1)
                : 0,
            'taux_retard' => $totalLivres > 0
                ? round((clone $livresBase)->where('ecart_livraison_jours', '>', 0)->count() / $totalLivres * 100, 1)
                : 0,
            'delai_moyen_livraison' => round(
                (clone $livresBase)->avg('ecart_livraison_jours') ?? 0, 1
            ),
            'ecart_cout_total' => round(
                $this->dossiersScopes()(DB::table('dossiers')
                    ->whereNotNull('cout_reel')
                    ->whereNotNull('cout_transitaire')
                    ->whereNull('deleted_at'))
                    ->selectRaw('SUM(cout_reel - cout_transitaire) as ecart')
                    ->value('ecart') ?? 0, 2
            ),
            'ecart_cout_pct' => round(
                $this->dossiersScopes()(DB::table('dossiers')
                    ->whereNotNull('cout_reel')
                    ->whereNotNull('cout_transitaire')
                    ->where('cout_transitaire', '>', 0)
                    ->whereNull('deleted_at'))
                    ->selectRaw('AVG((cout_reel - cout_transitaire) / cout_transitaire * 100) as pct')
                    ->value('pct') ?? 0, 1
            ),
        ];
    }

    private function loadAlertes(): void
    {
        $this->alertes = Dossier::withAlertes()
            ->tap($this->dossiersScopes())
            ->with(['client', 'user', 'etapeMadFournisseur', 'etapeLivraison'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn($d) => [
                'id'        => $d->id,
                'reference' => $d->reference,
                'client'    => $d->client->nom ?? '-',
                'statut'    => $d->statut_label,
                'color'     => $d->statut_color,
                'alertes'   => array_filter([
                    $d->alerte_retard_mad          ? 'MAD en retard' : null,
                    $d->alerte_facture_manquante   ? 'Facture manquante' : null,
                    $d->alerte_transitaire_manquant? 'Transitaire manquant' : null,
                    $d->alerte_livraison_depassee  ? 'Livraison dépassée' : null,
                    $d->alerte_pod_manquante       ? 'POD manquante' : null,
                ]),
            ])
            ->toArray();
    }

    private function loadCharts(): void
    {
        // Répartition par statut
        $statutData = Dossier::select('statut', DB::raw('count(*) as total'))
            ->tap($this->dossiersScopes())
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $labels = ['en_attente','mad_fournisseur','facture','transitaire_ok','enleve','livre','finalise'];
        $labelsNice = ['En attente','MAD Fourn.','Facturé','Transitaire OK','Enlevé','Livré','Finalisé'];
        $this->chartStatuts = [
            'labels' => $labelsNice,
            'data'   => array_map(fn($s) => $statutData[$s] ?? 0, $labels),
        ];

        // Coût prévu vs réel par mois
        $coutsMensuels = $this->dossiersScopes()(DB::table('dossiers')
            ->whereNotNull('cout_reel')
            ->whereNotNull('cout_transitaire')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subYear()))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('AVG(cout_transitaire) as moy_prevu'),
                DB::raw('AVG(cout_reel) as moy_reel'),
            )
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        $this->chartCouts = [
            'labels' => $coutsMensuels->map(fn($r) => sprintf('%02d/%d', $r->month, $r->year))->toArray(),
            'prevu'  => $coutsMensuels->map(fn($r) => round($r->moy_prevu, 2))->toArray(),
            'reel'   => $coutsMensuels->map(fn($r) => round($r->moy_reel, 2))->toArray(),
        ];

        // Dossiers par mois (12 derniers mois)
        $monthly = Dossier::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('count(*) as total')
            )
            ->tap($this->dossiersScopes())
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        $this->chartMensuel = [
            'labels' => $monthly->map(fn($r) => sprintf('%02d/%d', $r->month, $r->year))->toArray(),
            'data'   => $monthly->pluck('total')->toArray(),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app', ['title' => 'Tableau de bord']);
    }
}
