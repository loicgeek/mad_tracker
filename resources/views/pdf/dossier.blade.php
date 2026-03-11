<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dossier {{ $dossier->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; }
        h1 { font-size: 18px; color: #1a3ef5; margin: 0 0 4px; }
        h2 { font-size: 13px; color: #334155; margin: 16px 0 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.info td { padding: 4px 8px; border: 1px solid #e2e8f0; }
        table.info td:first-child { background: #f8fafc; font-weight: bold; width: 35%; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .step { margin-bottom: 10px; padding: 8px 12px; background: #f8fafc; border-left: 3px solid #2952ff; }
        .step h3 { font-size: 12px; margin: 0 0 4px; color: #1a3ef5; }
        .obs { border: 1px solid #e2e8f0; padding: 6px 8px; margin: 4px 0; font-size: 10px; }
        .footer { margin-top: 30px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

<table width="100%">
    <tr>
        <td>
            <h1>{{ $dossier->reference }}</h1>
            <p style="color:#64748b;margin:0">{{ $dossier->reference_affaire }} — {{ $dossier->numero_facture }}</p>
        </td>
        <td align="right">
            <span class="badge badge-blue">{{ $dossier->statut_label }}</span>
            <p style="font-size:9px;color:#94a3b8;margin:4px 0 0">Généré le {{ now()->format('d/m/Y H:i') }}</p>
        </td>
    </tr>
</table>

<h2>Informations générales</h2>
<table class="info">
    <tr><td>Responsable</td><td>{{ $dossier->user->nom_complet }} ({{ $dossier->user->initiales }})</td></tr>
    <tr><td>Client</td><td>{{ $dossier->client->nom }} — {{ $dossier->pays_destination }}</td></tr>
    <tr><td>Fournisseur</td><td>{{ $dossier->fournisseur?->nom ?? '—' }}</td></tr>
    <tr><td>Incoterm</td><td>{{ $dossier->incoterm_label }} — {{ $dossier->incoterm_lieu }}</td></tr>
    <tr><td>Transitaire</td><td>{{ $dossier->transitaire_nom ?: '—' }}</td></tr>
</table>

@php $mad = $dossier->etapeMadFournisseur; @endphp
@if($mad)
<div class="step">
    <h3>Étape 1 — MAD Fournisseur {{ $mad->complete ? '✓' : '…' }}</h3>
    <p>Prévue : {{ $mad->date_mad_prevue?->format('d/m/Y') ?: '—' }} | Réelle : {{ $mad->date_mad_reelle?->format('d/m/Y') ?: '—' }}
    @if(!is_null($mad->ecart_jours)) | Écart : {{ $mad->ecart_jours }}j @endif</p>
    <p>Docs : {{ $mad->docs_recus ? '✓' : '✗' }} | Photos : {{ $mad->photos_recues ? '✓' : '✗' }} | COC : {{ $mad->coc_recu ? '✓' : '✗' }}</p>
    @if($mad->observations)<p><em>{{ $mad->observations }}</em></p>@endif
</div>
@endif

@php $fact = $dossier->etapeFacturation; @endphp
@if($fact)
<div class="step">
    <h3>Étape 2 — Facturation {{ $fact->complete ? '✓' : '…' }}</h3>
    <p>Facture émise : {{ $fact->facture_emise ? 'OUI' : 'NON' }} | Paiement : {{ $fact->paiement_recu ? 'Reçu' : 'En attente' }}</p>
    @if($fact->montant)<p>Montant : {{ number_format($fact->montant, 2) }} {{ $fact->devise }}</p>@endif
</div>
@endif

@php $trans = $dossier->etapeTransitaire; @endphp
@if($trans)
<div class="step">
    <h3>Étape 3 — Transitaire {{ $trans->complete ? '✓' : '…' }}</h3>
    <p>Infos reçues : {{ $trans->transitaire_communique ? 'OUI' : 'NON' }}</p>
    @if($trans->date_enlevement)<p>Enlèvement : {{ $trans->date_enlevement->format('d/m/Y') }}</p>@endif
    @if($trans->temps_traitement_jours !== null)<p>Temps traitement : {{ $trans->temps_traitement_jours }}j</p>@endif
</div>
@endif

@php $liv = $dossier->etapeLivraison; @endphp
@if($liv)
<div class="step">
    <h3>Étape 4 — Livraison {{ $liv->complete ? '✓' : '…' }}</h3>
    <p>Prévue : {{ $liv->date_livraison_prevue?->format('d/m/Y') ?: '—' }} | Réelle : {{ $liv->date_livraison_reelle?->format('d/m/Y') ?: '—' }}</p>
    @if(!is_null($liv->ecart_jours))<p>Écart : {{ $liv->ecart_jours }}j</p>@endif
    @if($liv->awb_bl_numero)<p>AWB/BL : {{ $liv->awb_bl_numero }}</p>@endif
</div>
@endif

@php $clot = $dossier->etapeCloture; @endphp
@if($clot)
<div class="step" style="border-left-color: {{ $clot->pod_recue ? '#22c55e' : '#eab308' }}">
    <h3>Étape 5 — Clôture POD {{ $clot->pod_recue ? '✓ FINALISÉ' : '…' }}</h3>
    <p>POD reçue : {{ $clot->pod_recue ? 'OUI' : 'NON' }} @if($clot->date_pod)| Date : {{ $clot->date_pod->format('d/m/Y') }}@endif</p>
    @if($clot->pod_source)<p>Source : {{ $clot->pod_source }}</p>@endif
</div>
@endif

@if($dossier->observations->count() > 0)
<h2>Historique des observations</h2>
@foreach($dossier->observations as $obs)
<div class="obs">
    <strong>{{ $obs->type_icon }} {{ $obs->user->initiales ?? '?' }}</strong> · {{ ucfirst($obs->etape) }} · {{ $obs->created_at->format('d/m/Y H:i') }}<br>
    {{ $obs->contenu }}
</div>
@endforeach
@endif

<div class="footer">
    KEHITAA SARL — MAD Tracker — {{ $dossier->reference }} — {{ now()->format('d/m/Y') }}
</div>

</body>
</html>
