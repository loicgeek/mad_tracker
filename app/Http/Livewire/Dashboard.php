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

    public function mount(): void
    {
        $this->loadStats();
        $this->loadAlertes();
        $this->loadCharts();
    }

    private function loadStats(): void
    {
        $this->stats = [
            'total'        => Dossier::count(),
            'en_cours'     => Dossier::enCours()->count(),
            'finalises'    => Dossier::finalises()->count(),
            'avec_alertes' => Dossier::withAlertes()->count(),
            'clients'      => Client::count(),
            'fournisseurs' => Fournisseur::count(),
            'moy_traitement' => round(
                DB::table('etape_transitaires')
                    ->whereNotNull('temps_traitement_jours')
                    ->avg('temps_traitement_jours') ?? 0, 1
            ),
            'pod_en_attente' => Dossier::where('alerte_pod_manquante', true)->count(),
        ];
    }

    private function loadAlertes(): void
    {
        $this->alertes = Dossier::withAlertes()
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
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $labels = ['en_attente','mad_fournisseur','facture','transitaire_ok','enleve','livre','finalise'];
        $labelsNice = ['En attente','MAD Fourn.','Facturé','Transitaire OK','Enlevé','Livré','Finalisé'];
        $this->chartStatuts = [
            'labels' => $labelsNice,
            'data'   => array_map(fn($s) => $statutData[$s] ?? 0, $labels),
        ];

        // Dossiers par mois (12 derniers mois)
        $monthly = Dossier::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('count(*) as total')
            )
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
