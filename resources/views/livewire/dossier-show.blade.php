<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
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
            <a href="{{ route('export.dossier.pdf', $dossier->id) }}" class="btn-secondary btn-sm">
                PDF
            </a>
            <a href="{{ route('dossiers.edit', $dossier->id) }}" class="btn-primary btn-sm">
                Modifier
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-4 gap-4">
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

    {{-- Alertes --}}
    @if($dossier->has_alerte)
    <div class="alert alert-warning">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="flex flex-wrap gap-2">
            @if($dossier->alerte_retard_mad)          <span class="badge badge-red">MAD en retard</span>@endif
            @if($dossier->alerte_facture_manquante)   <span class="badge badge-yellow">Facture manquante</span>@endif
            @if($dossier->alerte_transitaire_manquant)<span class="badge badge-yellow">Transitaire manquant</span>@endif
            @if($dossier->alerte_livraison_depassee)  <span class="badge badge-red">Livraison dépassée</span>@endif
            @if($dossier->alerte_pod_manquante)       <span class="badge badge-yellow">POD manquante</span>@endif
        </div>
    </div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- Timeline --}}
        <div class="col-span-2 card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Suivi des étapes</h3>
                <span class="badge {{ $dossier->statut === 'finalise' ? 'badge-green' : 'badge-blue' }}">
                    {{ $dossier->statut_label }}
                </span>
            </div>
            <div class="card-body space-y-6">

                @php
                    $mad   = $dossier->etapeMadFournisseur;
                    $fact  = $dossier->etapeFacturation;
                    $trans = $dossier->etapeTransitaire;
                    $liv   = $dossier->etapeLivraison;
                    $clot  = $dossier->etapeCloture;

                    function dotClass($complete, $isActive, $hasAlerte = false) {
                        if ($hasAlerte) return 'timeline-dot-alert';
                        if ($complete)  return 'timeline-dot-done';
                        if ($isActive)  return 'timeline-dot-active';
                        return 'timeline-dot-pending';
                    }
                @endphp

                {{-- Étape 1 --}}
                <div class="timeline-step">
                    <div class="{{ dotClass($mad?->complete, in_array($dossier->statut, ['en_attente','mad_fournisseur']), $dossier->alerte_retard_mad) }}">
                        @if($mad?->complete) <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else <span class="text-xs font-bold text-slate-600">1</span>
                        @endif
                    </div>
                    <div class="flex-1 pb-6">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-slate-800">MAD Fournisseur</p>
                            @if($mad?->complete) <span class="badge badge-green">Complète</span> @endif
                        </div>
                        @if($mad)
                        <div class="text-xs text-slate-500 space-y-1">
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
                            <div class="flex gap-3 mt-2">
                                <span @class(['badge badge-sm', $mad->docs_recus ? 'badge-green' : 'badge-gray'])>Docs</span>
                                <span @class(['badge badge-sm', $mad->photos_recues ? 'badge-green' : 'badge-gray'])>Photos</span>
                                <span @class(['badge badge-sm', $mad->coc_recu ? 'badge-green' : 'badge-gray'])>COC</span>
                            </div>
                            @if($mad->observations)
                                <p class="text-slate-600 italic mt-1">{{ $mad->observations }}</p>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Étape 2 --}}
                <div class="timeline-step">
                    <div class="{{ dotClass($fact?->complete, $dossier->statut === 'facture', $dossier->alerte_facture_manquante) }}">
                        @if($fact?->complete) <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else <span class="text-xs font-bold text-slate-600">2</span>
                        @endif
                    </div>
                    <div class="flex-1 pb-6">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-slate-800">Facturation</p>
                            @if($fact?->complete) <span class="badge badge-green">Complète</span> @endif
                        </div>
                        @if($fact)
                        <div class="text-xs text-slate-500 space-y-1">
                            <p>Facture émise : <span @class(['font-semibold', 'text-emerald-600' => $fact->facture_emise, 'text-slate-400' => !$fact->facture_emise])>{{ $fact->facture_emise ? 'OUI' : 'NON' }}</span></p>
                            @if($fact->date_facturation) <p>Date : <strong>{{ $fact->date_facturation->format('d/m/Y') }}</strong></p> @endif
                            @if($fact->montant) <p>Montant : <strong>{{ number_format($fact->montant, 2) }} {{ $fact->devise }}</strong></p> @endif
                            <p>Paiement : <span @class(['font-semibold', 'text-emerald-600' => $fact->paiement_recu, 'text-amber-600' => !$fact->paiement_recu])>{{ $fact->paiement_recu ? 'Reçu' : 'En attente' }}</span></p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Étape 3 --}}
                <div class="timeline-step">
                    <div class="{{ dotClass($trans?->complete, $dossier->statut === 'transitaire_ok', $dossier->alerte_transitaire_manquant) }}">
                        @if($trans?->complete) <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else <span class="text-xs font-bold text-slate-600">3</span>
                        @endif
                    </div>
                    <div class="flex-1 pb-6">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-slate-800">Transitaire</p>
                            @if($trans?->complete) <span class="badge badge-green">Complète</span> @endif
                        </div>
                        @if($trans)
                        <div class="text-xs text-slate-500 space-y-1">
                            <p>Infos reçues : <span @class(['font-semibold', 'text-emerald-600' => $trans->transitaire_communique])>{{ $trans->transitaire_communique ? 'OUI' : 'NON' }}</span></p>
                            @if($trans->date_enlevement)
                                <p>Date enlèvement : <strong>{{ $trans->date_enlevement->format('d/m/Y') }}</strong></p>
                            @endif
                            @if($trans->temps_traitement_jours !== null)
                                <p>Temps traitement : <strong class="text-brand-600">{{ $trans->temps_traitement_jours }}j</strong> (MAD → enlèvement)</p>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Étape 4 --}}
                <div class="timeline-step">
                    <div class="{{ dotClass($liv?->complete, $dossier->statut === 'enleve', $dossier->alerte_livraison_depassee) }}">
                        @if($liv?->complete) <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else <span class="text-xs font-bold text-slate-600">4</span>
                        @endif
                    </div>
                    <div class="flex-1 pb-6">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-slate-800">Livraison Client</p>
                            @if(!($liv?->applicable ?? true)) <span class="badge badge-gray">N/A</span> @endif
                            @if($liv?->complete) <span class="badge badge-green">Complète</span> @endif
                        </div>
                        @if($liv)
                        <div class="text-xs text-slate-500 space-y-1">
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
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Étape 5 --}}
                <div class="timeline-step">
                    <div class="{{ dotClass($clot?->complete, $dossier->statut === 'livre', $dossier->alerte_pod_manquante) }}">
                        @if($clot?->complete) <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else <span class="text-xs font-bold text-slate-600">5</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-slate-800">Clôture — POD</p>
                            @if($clot?->pod_recue) <span class="badge badge-green">✓ FINALISÉ</span> @endif
                        </div>
                        @if($clot)
                        <div class="text-xs text-slate-500 space-y-1">
                            <p>POD reçue : <span @class(['font-semibold', 'text-emerald-600' => $clot->pod_recue, 'text-amber-600' => !$clot->pod_recue])>{{ $clot->pod_recue ? 'OUI' : 'En attente' }}</span></p>
                            @if($clot->date_pod) <p>Date : <strong>{{ $clot->date_pod->format('d/m/Y') }}</strong></p> @endif
                            @if($clot->pod_source) <p>Source : {{ $clot->pod_source }}</p> @endif
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- Observations --}}
        <div class="card flex flex-col">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Historique</h3>
                <span class="badge badge-gray">{{ $dossier->observations->count() }}</span>
            </div>

            {{-- Add observation --}}
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

            {{-- Observations list --}}
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
                        <button wire:click="deleteObservation({{ $obs->id }})" class="btn-icon text-xs text-red-300 hover:text-red-500">✕</button>
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
