<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\DossiersExport;
use App\Models\Dossier;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    public function dossiers(Request $request)
    {
        $filters = $request->only(['statut', 'client_id', 'user_id']);
        $filename = 'dossiers-mad-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new DossiersExport($filters), $filename);
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
