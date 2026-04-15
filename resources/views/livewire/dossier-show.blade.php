<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Header ───────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('dossiers.index') }}" class="text-slate-400 hover:text-slate-600 text-sm">← Dossiers</a>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 font-mono">{{ $dossier->reference }}</h2>
            <p class="text-slate-500 text-sm mt-1">
                {{ $dossier->reference_affaire }}
                @if($dossier->numero_facture) · {{ $dossier->numero_facture }} @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('export.dossier.pdf', $dossier->id) }}" class="btn-secondary btn-sm">PDF</a>
            <a href="{{ route('dossiers.edit', $dossier->id) }}" class="btn-primary btn-sm">Modifier</a>
        </div>
    </div>

    {{-- ── Action suggérée ─────────────────────────────────────── --}}
    <div class="flex items-start gap-3 px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="font-semibold text-blue-900">Action suggérée</p>
            <p>{{ $dossier->action_suggeree }}</p>
        </div>
    </div>

    {{-- ── Summary cards ────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Client</p>
            <p class="font-semibold text-slate-900">{{ $dossier->client->nom }}</p>
            <p class="text-xs text-slate-400">{{ $dossier->pays_destination }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Fournisseur</p>
            <p class="font-semibold text-slate-900">{{ $dossier->fournisseur?->nom ?? '—' }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Incoterm</p>
            <p class="font-semibold text-slate-900">{{ $dossier->incoterm_label }}</p>
            <p class="text-xs text-slate-400">{{ $dossier->incoterm_lieu }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Responsable</p>
            <p class="font-semibold text-slate-900">{{ $dossier->user->nom_complet }}</p>
            <span class="badge badge-gray text-xs">{{ $dossier->user->initiales }}</span>
        </div>
    </div>

    {{-- ── Transporteur + Coûts ──────────────────────────────────── --}}
    @if($dossier->transporteur_id || $dossier->cout_transitaire || $dossier->cout_reel || $dossier->type_commande)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @if($dossier->transporteur_id)
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Transporteur</p>
            <p class="font-semibold text-slate-900">{{ $dossier->transporteur->nom }}</p>
            @if($dossier->transporteur->contact_nom)
                <p class="text-xs text-slate-400">{{ $dossier->transporteur->contact_nom }}</p>
            @endif
        </div>
        @endif
        @if($dossier->type_commande)
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Type commande</p>
            <span class="badge {{ $dossier->type_commande === 'projet' ? 'badge-purple' : 'badge-gray' }} capitalize">
                {{ $dossier->type_commande }}
            </span>
        </div>
        @endif
        @if($dossier->cout_transitaire || $dossier->cout_reel)
        <div class="card p-4 {{ $dossier->transporteur_id && $dossier->type_commande ? '' : 'col-span-2' }}">
            <p class="text-xs text-slate-500 mb-1">Coûts transport</p>
            <div class="space-y-1">
                @if($dossier->cout_transitaire)
                    <p class="text-sm text-slate-600">Prévu : <span class="font-semibold">{{ number_format($dossier->cout_transitaire, 2) }} €</span></p>
                @endif
                @if($dossier->cout_reel)
                    <p class="text-sm text-slate-600">Réel : <span class="font-semibold">{{ number_format($dossier->cout_reel, 2) }} €</span></p>
                @endif
                @if($dossier->cout_transitaire && $dossier->cout_reel)
                    @php $ecart = $dossier->cout_reel - $dossier->cout_transitaire; @endphp
                    <p class="text-xs {{ $ecart > 0 ? 'text-red-500' : 'text-emerald-600' }} font-medium">
                        Écart : {{ $ecart > 0 ? '+' : '' }}{{ number_format($ecart, 2) }} €
                    </p>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Alertes ──────────────────────────────────────────────── --}}
    @if($dossier->has_alerte)
    <div class="alert alert-warning">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="flex flex-wrap gap-2">
            @if($dossier->alerte_retard_mad)           <span class="badge badge-red">MAD en retard</span>@endif
            @if($dossier->alerte_facture_manquante)    <span class="badge badge-yellow">Facture manquante</span>@endif
            @if($dossier->alerte_transitaire_manquant) <span class="badge badge-yellow">Transitaire manquant</span>@endif
            @if($dossier->alerte_livraison_depassee)   <span class="badge badge-red">Livraison dépassée</span>@endif
            @if($dossier->alerte_pod_manquante)        <span class="badge badge-yellow">POD manquante</span>@endif
        </div>
    </div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- ── Timeline ────────────────────────────────────────── --}}
        <div class="col-span-2 card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Suivi des étapes</h3>
                {{-- Badge uses the model accessor → always in sync with index/form --}}
                <span class="badge {{ $dossier->statut_badge }}">{{ $dossier->statut_label }}</span>
            </div>
            <div class="card-body space-y-0 divide-y divide-slate-100">

                @php
                    $mad   = $dossier->etapeMadFournisseur;
                    $fact  = $dossier->etapeFacturation;
                    $trans = $dossier->etapeTransitaire;
                    $liv   = $dossier->etapeLivraison;
                    $clot  = $dossier->etapeCloture;

                    /*
                     * Per-étape visual config — mirrors form step colors exactly.
                     *
                     * Each entry:
                     *   dotDone     Tailwind classes when complete (always emerald)
                     *   dotActive   classes when in-progress (step color)
                     *   dotAlert    classes when alerted (red)
                     *   dotPending  classes when not yet started
                     *   accentBar   left accent bar color (step color)
                     *   headerText  label text color when active/in-progress
                     */
                    $stepConfig = [
                        'mad' => [
                            'dotActive'  => 'bg-blue-500 ring-2 ring-blue-200 text-white',
                            'accentBar'  => 'bg-blue-500',
                            'headerText' => 'text-blue-700',
                            'badgeDone'  => 'badge-blue',
                        ],
                        'fact' => [
                            'dotActive'  => 'bg-purple-500 ring-2 ring-purple-200 text-white',
                            'accentBar'  => 'bg-purple-500',
                            'headerText' => 'text-purple-700',
                            'badgeDone'  => 'badge-purple',
                        ],
                        'trans' => [
                            'dotActive'  => 'bg-yellow-500 ring-2 ring-yellow-200 text-white',
                            'accentBar'  => 'bg-yellow-500',
                            'headerText' => 'text-yellow-700',
                            'badgeDone'  => 'badge-yellow',
                        ],
                        'liv' => [
                            'dotActive'  => 'bg-amber-500 ring-2 ring-amber-200 text-white',
                            'accentBar'  => 'bg-amber-500',
                            'headerText' => 'text-amber-700',
                            'badgeDone'  => 'badge-amber',
                        ],
                        'clot' => [
                            'dotActive'  => 'bg-teal-500 ring-2 ring-teal-200 text-white',
                            'accentBar'  => 'bg-teal-500',
                            'headerText' => 'text-teal-700',
                            'badgeDone'  => 'badge-teal',
                        ],
                    ];

                    /*
                     * Resolve the dot class for a given étape:
                     *   alerted  → red ring
                     *   complete → solid emerald
                     *   exists   → step color (in progress)
                     *   null     → slate (not started)
                     */
                    $resolveDot = function(?object $etape, string $key, bool $hasAlerte = false) use ($stepConfig): array {
                        $cfg = $stepConfig[$key];
                        if ($hasAlerte && !($etape?->complete)) {
                            return [
                                'dot'   => 'bg-red-500 ring-2 ring-red-200 text-white',
                                'label' => 'text-red-600',
                            ];
                        }
                        if ($etape?->complete) {
                            return [
                                'dot'   => 'bg-emerald-500 text-white',
                                'label' => 'text-emerald-700',
                            ];
                        }
                        if ($etape) {
                            return [
                                'dot'   => $cfg['dotActive'],
                                'label' => $cfg['headerText'],
                            ];
                        }
                        return [
                            'dot'   => 'bg-slate-100 ring-1 ring-slate-200 text-slate-400',
                            'label' => 'text-slate-400',
                        ];
                    };
                @endphp

                {{-- ══ Étape 1 — MAD Fournisseur ══════════════════ --}}
                @php $r = $resolveDot($mad, 'mad', $dossier->alerte_retard_mad); @endphp
                <div class="flex gap-4 py-5">
                    {{-- Accent bar --}}
                    <div class="w-1 rounded-full self-stretch {{ $mad ? $stepConfig['mad']['accentBar'] : 'bg-slate-200' }} flex-shrink-0"></div>
                    {{-- Dot --}}
                    <div class="flex-shrink-0 mt-0.5">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $r['dot'] }}">
                            @if($mad?->complete)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else 1 @endif
                        </span>
                    </div>
                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold {{ $r['label'] }}">MAD Fournisseur</p>
                            @if($mad?->complete) <span class="badge badge-emerald text-xs">Complète</span>
                            @elseif($mad) <span class="badge {{ $stepConfig['mad']['badgeDone'] }} text-xs">En cours</span>
                            @endif
                        </div>
                        @if($mad)
                        <div class="text-xs text-slate-500 space-y-1.5">
                            @if($mad->date_mad_prevue)
                                <p>Prévue : <strong>{{ $mad->date_mad_prevue->format('d/m/Y') }}</strong></p>
                            @endif
                            @if($mad->date_mad_reelle)
                                <p>Réelle : <strong>{{ $mad->date_mad_reelle->format('d/m/Y') }}</strong>
                                    @if(!is_null($mad->ecart_jours))
                                        <span @class(['font-semibold ml-1', 'text-red-600' => $mad->ecart_jours > 0, 'text-emerald-600' => $mad->ecart_jours <= 0])>
                                            {{ $mad->ecart_jours > 0 ? "+{$mad->ecart_jours}j" : "{$mad->ecart_jours}j" }}
                                        </span>
                                    @endif
                                </p>
                            @endif
                            <div class="flex gap-2 mt-1">
                                <span @class(['badge badge-sm', 'badge-emerald' => $mad->docs_recus,   'badge-gray' => !$mad->docs_recus])>Docs</span>
                                <span @class(['badge badge-sm', 'badge-emerald' => $mad->photos_recues,'badge-gray' => !$mad->photos_recues])>Photos</span>
                                <span @class(['badge badge-sm', 'badge-emerald' => $mad->coc_recu,     'badge-gray' => !$mad->coc_recu])>COC</span>
                            </div>
                            @if($mad->observations)
                                <p class="text-slate-600 italic">{{ $mad->observations }}</p>
                            @endif
                        </div>
                        @else
                            <p class="text-xs text-slate-400 italic">Non démarrée</p>
                        @endif
                    </div>
                </div>

                {{-- ══ Étape 2 — Facturation ═══════════════════════ --}}
                @php $r = $resolveDot($fact, 'fact', $dossier->alerte_facture_manquante); @endphp
                <div class="flex gap-4 py-5">
                    <div class="w-1 rounded-full self-stretch {{ $fact ? $stepConfig['fact']['accentBar'] : 'bg-slate-200' }} flex-shrink-0"></div>
                    <div class="flex-shrink-0 mt-0.5">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $r['dot'] }}">
                            @if($fact?->complete)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else 2 @endif
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold {{ $r['label'] }}">Facturation</p>
                            @if($fact?->complete) <span class="badge badge-emerald text-xs">Complète</span>
                            @elseif($fact) <span class="badge {{ $stepConfig['fact']['badgeDone'] }} text-xs">En cours</span>
                            @endif
                        </div>
                        @if($fact)
                        <div class="text-xs text-slate-500 space-y-1.5">
                            <p>Facture émise :
                                <span @class(['font-semibold', 'text-emerald-600' => $fact->facture_emise, 'text-slate-400' => !$fact->facture_emise])>
                                    {{ $fact->facture_emise ? 'OUI' : 'NON' }}
                                </span>
                            </p>
                            @if($fact->date_facturation) <p>Date : <strong>{{ $fact->date_facturation->format('d/m/Y') }}</strong></p> @endif
                            @if($fact->montant) <p>Montant : <strong>{{ number_format($fact->montant, 2) }} {{ $fact->devise }}</strong></p> @endif
                            <p>Paiement :
                                <span @class(['font-semibold', 'text-emerald-600' => $fact->paiement_recu, 'text-amber-600' => !$fact->paiement_recu])>
                                    {{ $fact->paiement_recu ? 'Reçu' : 'En attente' }}
                                </span>
                            </p>
                            @if($fact->observations) <p class="text-slate-600 italic">{{ $fact->observations }}</p> @endif
                        </div>
                        @else
                            <p class="text-xs text-slate-400 italic">Non démarrée</p>
                        @endif
                    </div>
                </div>

                {{-- ══ Étape 3 — Transitaire ═══════════════════════ --}}
                @php $r = $resolveDot($trans, 'trans', $dossier->alerte_transitaire_manquant); @endphp
                <div class="flex gap-4 py-5">
                    <div class="w-1 rounded-full self-stretch {{ $trans ? $stepConfig['trans']['accentBar'] : 'bg-slate-200' }} flex-shrink-0"></div>
                    <div class="flex-shrink-0 mt-0.5">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $r['dot'] }}">
                            @if($trans?->complete)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else 3 @endif
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold {{ $r['label'] }}">Transitaire</p>
                            @if($trans?->complete) <span class="badge badge-emerald text-xs">Complète</span>
                            @elseif($trans) <span class="badge {{ $stepConfig['trans']['badgeDone'] }} text-xs">En cours</span>
                            @endif
                        </div>
                        @if($trans)
                        <div class="text-xs text-slate-500 space-y-1.5">
                            <p>Infos reçues :
                                <span @class(['font-semibold', 'text-emerald-600' => $trans->transitaire_communique])>
                                    {{ $trans->transitaire_communique ? 'OUI' : 'NON' }}
                                </span>
                            </p>
                            @if($trans->date_enlevement)
                                <p>Date enlèvement : <strong>{{ $trans->date_enlevement->format('d/m/Y') }}</strong></p>
                            @endif
                            @if($trans->temps_traitement_jours !== null)
                                <p>Temps traitement : <strong class="text-brand-600">{{ $trans->temps_traitement_jours }}j</strong> (MAD → enlèvement)</p>
                            @endif
                            @if($trans->observations) <p class="text-slate-600 italic">{{ $trans->observations }}</p> @endif
                        </div>
                        @else
                            <p class="text-xs text-slate-400 italic">Non démarrée</p>
                        @endif
                    </div>
                </div>

                {{-- ══ Étape 4a — Livraison ════════════════════════ --}}
                @php $r = $resolveDot($liv, 'liv', $dossier->alerte_livraison_depassee); @endphp
                <div class="flex gap-4 py-5">
                    <div class="w-1 rounded-full self-stretch {{ $liv ? $stepConfig['liv']['accentBar'] : 'bg-slate-200' }} flex-shrink-0"></div>
                    <div class="flex-shrink-0 mt-0.5">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $r['dot'] }}">
                            @if($liv?->complete)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else 4 @endif
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold {{ $r['label'] }}">Livraison Client</p>
                            @if(!($liv?->applicable ?? true)) <span class="badge badge-gray text-xs">N/A</span>
                            @elseif($liv?->complete) <span class="badge badge-emerald text-xs">Complète</span>
                            @elseif($liv) <span class="badge {{ $stepConfig['liv']['badgeDone'] }} text-xs">En cours</span>
                            @endif
                        </div>
                        @if($liv)
                        <div class="text-xs text-slate-500 space-y-1.5">
                            @if($liv->date_livraison_prevue) <p>Prévue : <strong>{{ $liv->date_livraison_prevue->format('d/m/Y') }}</strong></p> @endif
                            @if($liv->date_livraison_reelle)
                                <p>Réelle : <strong>{{ $liv->date_livraison_reelle->format('d/m/Y') }}</strong>
                                    @if(!is_null($liv->ecart_jours))
                                        <span @class(['font-semibold ml-1', 'text-red-600' => $liv->ecart_jours > 0, 'text-emerald-600' => $liv->ecart_jours <= 0])>
                                            {{ $liv->ecart_jours > 0 ? "+{$liv->ecart_jours}j" : "{$liv->ecart_jours}j" }}
                                        </span>
                                    @endif
                                </p>
                            @endif
                            @if($liv->awb_bl_numero) <p>AWB/BL : <strong>{{ $liv->awb_bl_numero }}</strong></p> @endif
                            @if($liv->observations) <p class="text-slate-600 italic">{{ $liv->observations }}</p> @endif
                        </div>
                        @else
                            <p class="text-xs text-slate-400 italic">Non démarrée</p>
                        @endif
                    </div>
                </div>

                {{-- ══ Étape 4b — Clôture (POD) ════════════════════ --}}
                @php $r = $resolveDot($clot, 'clot', $dossier->alerte_pod_manquante); @endphp
                <div class="flex gap-4 py-5">
                    <div class="w-1 rounded-full self-stretch {{ $clot ? $stepConfig['clot']['accentBar'] : 'bg-slate-200' }} flex-shrink-0"></div>
                    <div class="flex-shrink-0 mt-0.5">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $r['dot'] }}">
                            @if($clot?->complete)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else 5 @endif
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold {{ $r['label'] }}">Clôture — POD</p>
                            @if($clot?->pod_recue) <span class="badge badge-teal text-xs">✓ Finalisé</span>
                            @elseif($clot) <span class="badge {{ $stepConfig['clot']['badgeDone'] }} text-xs">En cours</span>
                            @endif
                        </div>
                        @if($clot)
                        <div class="text-xs text-slate-500 space-y-1.5">
                            <p>POD reçue :
                                <span @class(['font-semibold', 'text-emerald-600' => $clot->pod_recue, 'text-amber-600' => !$clot->pod_recue])>
                                    {{ $clot->pod_recue ? 'OUI' : 'En attente' }}
                                </span>
                            </p>
                            @if($clot->date_pod) <p>Date : <strong>{{ $clot->date_pod->format('d/m/Y') }}</strong></p> @endif
                            @if($clot->pod_reference) <p>Référence : {{ $clot->pod_reference }}</p> @endif
                            @if($clot->pod_source) <p>Source : {{ $clot->pod_source }}</p> @endif
                            @if($clot->observations) <p class="text-slate-600 italic">{{ $clot->observations }}</p> @endif
                        </div>
                        @else
                            <p class="text-xs text-slate-400 italic">Non démarrée</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Observations ─────────────────────────────────────── --}}
        <div class="card flex flex-col">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Historique</h3>
                <span class="badge badge-gray">{{ $dossier->observations->count() }}</span>
            </div>

            <div class="px-5 py-4 border-b border-slate-100 space-y-3">
                <textarea wire:model="newObservation" rows="3" class="form-textarea text-sm"
                          placeholder="Ajouter une observation…"></textarea>
                <div class="flex items-center gap-2">
                    <select wire:model="newObservationEtape" class="form-select text-xs flex-1">
                        <option value="general">Général</option>
                        <option value="mad_fournisseur">MAD Fourn.</option>
                        <option value="facturation">Facturation</option>
                        <option value="transitaire">Transitaire</option>
                        <option value="livraison">Livraison</option>
                        <option value="cloture">Clôture</option>
                    </select>
                    <select wire:model="newObservationType" class="form-select text-xs flex-1">
                        <option value="info">Info</option>
                        <option value="alerte">Alerte</option>
                        <option value="blocage">Blocage</option>
                        <option value="resolution">Résolution</option>
                    </select>
                </div>
                <button wire:click="addObservation" class="btn-primary btn-sm w-full justify-center">
                    Ajouter
                </button>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-slate-100" style="max-height: 400px">
                @forelse($dossier->observations as $obs)
                <div class="px-5 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1">
                            <p class="text-xs text-slate-400 mb-1">
                                {{ $obs->type_icon }} {{ $obs->user->initiales ?? '?' }}
                                · {{ ucfirst($obs->etape) }}
                                · {{ $obs->created_at->format('d/m/Y H:i') }}
                            </p>
                            <p class="text-sm text-slate-700">{{ $obs->contenu }}</p>
                        </div>
                        @if($obs->user_id === auth()->id())
                        <button wire:click="deleteObservation({{ $obs->id }})"
                                class="btn-icon text-xs text-red-300 hover:text-red-500">✕</button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-slate-400">Aucune observation</div>
                @endforelse
            </div>
        </div>

    </div>
</div>