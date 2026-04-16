<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\DossiersExport;
use App\Models\Dossier;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    public function dossiers(Request $request)
    {
        $filters = $request->only(['statut', 'client_id', 'user_id']);
        $filename = 'dossiers-mad-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new DossiersExport($filters), $filename);
    }

    public function analyses(Request $request)
    {
        $periode = (int) $request->query('periode', 12);
        $since   = now()->subMonths($periode);

        $parFournisseur = DB::table('dossiers')
            ->join('fournisseurs', 'fournisseurs.id', '=', 'dossiers.fournisseur_id')
            ->leftJoin('etape_transitaires', 'etape_transitaires.dossier_id', '=', 'dossiers.id')
            ->where('dossiers.created_at', '>=', $since)
            ->groupBy('fournisseurs.id', 'fournisseurs.nom')
            ->select('fournisseurs.nom',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN dossiers.statut = "finalise" THEN 1 ELSE 0 END) as finalises'),
                DB::raw('ROUND(AVG(etape_transitaires.temps_traitement_jours), 1) as moy_traitement'))
            ->orderByDesc('total')->get();

        $parClient = DB::table('dossiers')
            ->join('clients', 'clients.id', '=', 'dossiers.client_id')
            ->where('dossiers.created_at', '>=', $since)
            ->groupBy('clients.id', 'clients.nom')
            ->select('clients.nom',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN dossiers.statut = "finalise" THEN 1 ELSE 0 END) as finalises'))
            ->orderByDesc('total')->get();

        $parIncoterm = DB::table('dossiers')
            ->where('created_at', '>=', $since)
            ->groupBy('incoterm')
            ->select('incoterm',
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND(AVG((SELECT temps_traitement_jours FROM etape_transitaires WHERE dossier_id = dossiers.id)), 1) as moy_traitement'))
            ->orderByDesc('total')->get();

        $parResponsable = DB::table('dossiers')
            ->join('users', 'users.id', '=', 'dossiers.user_id')
            ->where('dossiers.created_at', '>=', $since)
            ->groupBy('users.id', 'users.nom', 'users.prenom', 'users.initiales')
            ->select(DB::raw('CONCAT(users.prenom, " ", users.nom) as nom'), 'users.initiales',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN dossiers.statut = "finalise" THEN 1 ELSE 0 END) as finalises'),
                DB::raw('SUM(CASE WHEN dossiers.alerte_retard_mad = 1 OR dossiers.alerte_livraison_depassee = 1 THEN 1 ELSE 0 END) as alertes'))
            ->orderByDesc('total')->get();

        $parTransporteur = DB::table('dossiers')
            ->join('transporteurs', 'transporteurs.id', '=', 'dossiers.transporteur_id')
            ->leftJoin('etape_livraisons', 'etape_livraisons.dossier_id', '=', 'dossiers.id')
            ->where('dossiers.created_at', '>=', $since)->whereNull('dossiers.deleted_at')
            ->groupBy('transporteurs.id', 'transporteurs.nom')
            ->select('transporteurs.nom',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN etape_livraisons.ecart_livraison_jours <= 0 AND etape_livraisons.date_livraison_reelle IS NOT NULL THEN 1 ELSE 0 END) as livres_a_temps'),
                DB::raw('SUM(CASE WHEN etape_livraisons.date_livraison_reelle IS NOT NULL THEN 1 ELSE 0 END) as total_livres'),
                DB::raw('ROUND(AVG(dossiers.cout_transitaire), 0) as moy_cout_prevu'),
                DB::raw('ROUND(AVG(dossiers.cout_reel), 0) as moy_cout_reel'))
            ->orderByDesc('total')->get()
            ->map(function ($r) {
                $r->pct_a_temps = $r->total_livres > 0 ? round($r->livres_a_temps / $r->total_livres * 100, 1) : null;
                $r->ecart_pct   = ($r->moy_cout_prevu > 0 && $r->moy_cout_reel !== null)
                    ? round(($r->moy_cout_reel - $r->moy_cout_prevu) / $r->moy_cout_prevu * 100, 1) : null;
                return $r;
            });

        $parType = DB::table('dossiers')
            ->where('dossiers.created_at', '>=', $since)->whereNull('dossiers.deleted_at')
            ->whereIn('type_commande', ['standard', 'projet'])
            ->leftJoin('etape_livraisons', 'etape_livraisons.dossier_id', '=', 'dossiers.id')
            ->groupBy('dossiers.type_commande')
            ->select('dossiers.type_commande',
                DB::raw('COUNT(dossiers.id) as total'),
                DB::raw('SUM(CASE WHEN dossiers.statut = "finalise" THEN 1 ELSE 0 END) as finalises'),
                DB::raw('ROUND(AVG(etape_livraisons.ecart_livraison_jours), 1) as delai_moyen'))
            ->get()
            ->map(fn($r) => tap($r, fn($r) => $r->taux = $r->total > 0 ? round($r->finalises / $r->total * 100, 1) : 0));

        $delaiParIncoterm = DB::table('dossiers')
            ->join('etape_livraisons', 'etape_livraisons.dossier_id', '=', 'dossiers.id')
            ->where('dossiers.created_at', '>=', $since)->whereNull('dossiers.deleted_at')
            ->whereNotNull('etape_livraisons.ecart_livraison_jours')
            ->groupBy('dossiers.incoterm')
            ->select('dossiers.incoterm',
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND(AVG(etape_livraisons.ecart_livraison_jours), 1) as delai_moyen'))
            ->orderByDesc('total')->get();

        $filename = 'analyses-mad-' . now()->format('Y-m-d') . '.csv';

        $callback = function () use ($parFournisseur, $parClient, $parIncoterm, $parResponsable, $parTransporteur, $parType, $delaiParIncoterm, $periode) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            // Section 1 — Par fournisseur
            fputcsv($out, ["=== Par fournisseur (periode: {$periode} mois) ==="], ';');
            fputcsv($out, ['Fournisseur', 'Total', 'Finalisés', 'Moy. traitement (j)'], ';');
            foreach ($parFournisseur as $r) {
                fputcsv($out, [$r->nom, $r->total, $r->finalises, $r->moy_traitement ?? ''], ';');
            }

            // Section 2 — Par client
            fputcsv($out, [], ';');
            fputcsv($out, ["=== Par client ==="], ';');
            fputcsv($out, ['Client', 'Total', 'Finalisés', 'Taux (%)'], ';');
            foreach ($parClient as $r) {
                $taux = $r->total > 0 ? round($r->finalises / $r->total * 100, 1) : 0;
                fputcsv($out, [$r->nom, $r->total, $r->finalises, $taux], ';');
            }

            // Section 3 — Par incoterm
            fputcsv($out, [], ';');
            fputcsv($out, ["=== Par incoterm ==="], ';');
            fputcsv($out, ['Incoterm', 'Total', 'Moy. traitement (j)'], ';');
            foreach ($parIncoterm as $r) {
                fputcsv($out, [$r->incoterm, $r->total, $r->moy_traitement ?? ''], ';');
            }

            // Section 4 — Par responsable
            fputcsv($out, [], ';');
            fputcsv($out, ["=== Par responsable ==="], ';');
            fputcsv($out, ['Responsable', 'Initiales', 'Total', 'Finalisés', 'Alertes'], ';');
            foreach ($parResponsable as $r) {
                fputcsv($out, [$r->nom, $r->initiales, $r->total, $r->finalises, $r->alertes], ';');
            }

            // Section 5 — Par transporteur
            fputcsv($out, [], ';');
            fputcsv($out, ["=== Par transporteur ==="], ';');
            fputcsv($out, ['Transporteur', 'Total', '% à temps', 'Coût prévu moy. (€)', 'Coût réel moy. (€)', 'Écart (%)'], ';');
            foreach ($parTransporteur as $r) {
                fputcsv($out, [
                    $r->nom, $r->total,
                    $r->pct_a_temps !== null ? $r->pct_a_temps : '',
                    $r->moy_cout_prevu ?? '',
                    $r->moy_cout_reel ?? '',
                    $r->ecart_pct !== null ? $r->ecart_pct : '',
                ], ';');
            }

            // Section 6 — Standard vs Projet
            fputcsv($out, [], ';');
            fputcsv($out, ["=== Standard vs Projet ==="], ';');
            fputcsv($out, ['Type', 'Total', 'Finalisés', 'Taux (%)', 'Délai moyen (j)'], ';');
            foreach ($parType as $r) {
                fputcsv($out, [ucfirst($r->type_commande), $r->total, $r->finalises, $r->taux, $r->delai_moyen ?? ''], ';');
            }

            // Section 7 — Délai par incoterm
            fputcsv($out, [], ';');
            fputcsv($out, ["=== Délai moyen de livraison par incoterm ==="], ';');
            fputcsv($out, ['Incoterm', 'Dossiers livrés', 'Délai moyen (j)'], ';');
            foreach ($delaiParIncoterm as $r) {
                fputcsv($out, [$r->incoterm, $r->total, $r->delai_moyen], ';');
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function dossierPdf(int $id)
    {
        $dossier = Dossier::with([
            'client', 'user', 'fournisseur',
            'etapeMadFournisseur', 'etapeFacturation',
            'etapeTransitaire', 'etapeLivraison', 'etapeCloture',
            'observations.user',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.dossier', compact('dossier'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("dossier-{$dossier->reference}.pdf");
    }
}
