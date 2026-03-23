<div class="max-w-4xl mx-auto space-y-6">

    {{-- ── Header ───────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">{{ $title }}</h2>
            @if($isEdit)
                <p class="text-sm font-mono text-slate-500 mt-0.5">{{ $dossier->reference }}</p>
            @else
                <p class="text-sm text-slate-500 mt-0.5">Remplissez les informations étape par étape</p>
            @endif
        </div>
        <a href="{{ route('dossiers.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>

    {{-- ── Step navigator ──────────────────────────────────────── --}}
    @php
        /*
         * 5 wizard steps, but only 4 are "étapes métier":
         *   Step 1 — Infos générales  (no étape model, slate)
         *   Step 2 — MAD Fournisseur  (blue)
         *   Step 3 — Facturation      (purple)
         *   Step 4 — Transitaire      (yellow)
         *   Step 5 — Livraison & Clôture (combined: amber → teal)
         *
         * For step 5 "complete" we require both liv_complete AND clot_complete,
         * matching recalculerStatut() which reaches 'finalise' only when
         * etapeCloture->complete is true.
         */
        $stepDefs = [
            1 => [
                'label'            => 'Infos générales',
                'sublabel'         => 'Général',
                'complete'         => $client_id > 0 && $user_id > 0,
                'hasData'          => $client_id > 0 || $user_id > 0,
                'bubbleActive'     => 'bg-slate-700 border-slate-700 text-white ring-2 ring-slate-300',
                'bubbleInProgress' => 'bg-slate-400 border-slate-400 text-white',
                'borderTop'        => 'border-t-4 border-slate-600',
                'headerBg'         => 'bg-slate-50 border-b border-slate-200',
                'headerText'       => 'text-slate-800',
                'labelActive'      => 'text-slate-700',
                'connDone'         => 'bg-slate-400',
                'checkAccent'      => 'accent-slate-600',
            ],
            2 => [
                'label'            => 'MAD Fournisseur',
                'sublabel'         => 'Étape 1',
                'complete'         => $mad_complete,
                'hasData'          => (bool)($mad_date_prevue || $mad_docs_recus || $mad_photos_recues || $mad_coc_recu),
                'bubbleActive'     => 'bg-blue-600 border-blue-600 text-white ring-2 ring-blue-200',
                'bubbleInProgress' => 'bg-blue-400 border-blue-400 text-white',
                'borderTop'        => 'border-t-4 border-blue-500',
                'headerBg'         => 'bg-blue-50 border-b border-blue-200',
                'headerText'       => 'text-blue-800',
                'labelActive'      => 'text-blue-700',
                'connDone'         => 'bg-blue-300',
                'checkAccent'      => 'accent-blue-600',
            ],
            3 => [
                'label'            => 'Facturation',
                'sublabel'         => 'Étape 2',
                'complete'         => $fact_complete,
                'hasData'          => (bool)($fact_emise || $fact_date || $fact_montant),
                'bubbleActive'     => 'bg-purple-600 border-purple-600 text-white ring-2 ring-purple-200',
                'bubbleInProgress' => 'bg-purple-400 border-purple-400 text-white',
                'borderTop'        => 'border-t-4 border-purple-500',
                'headerBg'         => 'bg-purple-50 border-b border-purple-200',
                'headerText'       => 'text-purple-800',
                'labelActive'      => 'text-purple-700',
                'connDone'         => 'bg-purple-300',
                'checkAccent'      => 'accent-purple-600',
            ],
            4 => [
                'label'            => 'Transitaire',
                'sublabel'         => 'Étape 3',
                'complete'         => $trans_complete,
                'hasData'          => (bool)($trans_communique || $trans_date_enlevement),
                'bubbleActive'     => 'bg-yellow-500 border-yellow-500 text-white ring-2 ring-yellow-200',
                'bubbleInProgress' => 'bg-yellow-300 border-yellow-300 text-white',
                'borderTop'        => 'border-t-4 border-yellow-500',
                'headerBg'         => 'bg-yellow-50 border-b border-yellow-200',
                'headerText'       => 'text-yellow-800',
                'labelActive'      => 'text-yellow-700',
                'connDone'         => 'bg-yellow-300',
                'checkAccent'      => 'accent-yellow-600',
            ],
            5 => [
                /*
                 * Combined step. The bubble color shifts:
                 *   - If clôture has data / is active → teal
                 *   - Otherwise                       → amber (livraison phase)
                 * "complete" requires both liv_complete AND clot_complete.
                 */
                'label'            => 'Livraison & Clôture',
                'sublabel'         => 'Étape 4',
                'complete'         => $liv_complete && $clot_complete,
                'hasData'          => (bool)($liv_date_prevue || $liv_awb || $clot_pod_recue || $clot_date_pod),
                // Active bubble: teal if clôture touched, amber if only livraison
                'bubbleActive'     => ($clot_pod_recue || $clot_date_pod || $clot_complete)
                                        ? 'bg-teal-600 border-teal-600 text-white ring-2 ring-teal-200'
                                        : 'bg-amber-500 border-amber-500 text-white ring-2 ring-amber-200',
                'bubbleInProgress' => ($clot_pod_recue || $clot_date_pod)
                                        ? 'bg-teal-400 border-teal-400 text-white'
                                        : 'bg-amber-400 border-amber-400 text-white',
                // Card border always teal (it's the "final" étape)
                'borderTop'        => 'border-t-4 border-teal-500',
                'headerBg'         => 'bg-teal-50 border-b border-teal-200',
                'headerText'       => 'text-teal-800',
                'labelActive'      => ($clot_pod_recue || $clot_date_pod || $clot_complete)
                                        ? 'text-teal-700'
                                        : 'text-amber-700',
                'connDone'         => 'bg-teal-300',
                'checkAccent'      => 'accent-teal-600',
            ],
        ];

        $current = $stepDefs[$currentStep];
    @endphp

    <div class="card">
        <div class="card-body py-4">
            <nav aria-label="Étapes du dossier">
                <ol class="flex items-center">
                    @foreach($stepDefs as $n => $s)
                        @php
                            $isActive     = $n === $currentStep;
                            $isDone       = $s['complete'];
                            $isInProgress = !$isDone && $s['hasData'];
                            $isLast       = $n === count($stepDefs);
                        @endphp
                        <li class="flex items-center {{ $isLast ? '' : 'flex-1' }}">

                            <button wire:click="goToStep({{ $n }})" type="button"
                                    class="flex flex-col items-center gap-1 group focus:outline-none"
                                    aria-current="{{ $isActive ? 'step' : 'false' }}">

                                {{-- Bubble --}}
                                <span @class([
                                    'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold border-2 transition-all duration-200',
                                    'bg-emerald-500 border-emerald-500 text-white ring-2 ring-emerald-200'        => $isDone && !$isActive,
                                    $s['bubbleActive'] . ' scale-110'                                              => $isActive,
                                    $s['bubbleInProgress'] . ' opacity-75'                                         => $isInProgress && !$isActive && !$isDone,
                                    'bg-white border-slate-300 text-slate-400 group-hover:bg-slate-50'             => !$isDone && !$isInProgress && !$isActive,
                                ])>
                                    @if($isDone && !$isActive)
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{-- Show étape number (1-4) for steps 2-5, 0 for step 1 --}}
                                        {{ $n === 1 ? '✦' : ($n - 1) }}
                                    @endif
                                </span>

                                {{-- Labels --}}
                                <span class="hidden md:flex flex-col items-center leading-tight text-center">
                                    <span @class([
                                        'text-xs font-semibold transition-colors whitespace-nowrap',
                                        $s['labelActive']             => $isActive,
                                        'text-emerald-600'            => $isDone && !$isActive,
                                        $s['labelActive'] . ' opacity-60' => $isInProgress && !$isActive && !$isDone,
                                        'text-slate-400'              => !$isActive && !$isDone && !$isInProgress,
                                    ])>{{ $s['label'] }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $s['sublabel'] }}</span>
                                </span>
                            </button>

                            {{-- Connector --}}
                            @unless($isLast)
                                <div @class([
                                    'flex-1 h-0.5 mx-2 rounded transition-colors duration-300',
                                    $s['connDone'] => $s['complete'],
                                    'bg-slate-200' => !$s['complete'],
                                ])></div>
                            @endunless
                        </li>
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- ── Step panels ─────────────────────────────────────────── --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}

    {{-- ══ Step 1 : Informations générales ═══════════════════════ --}}
    @if($currentStep === 1)
    <div class="card {{ $current['borderTop'] }}">
        <div class="card-header {{ $current['headerBg'] }}">
            <h3 class="font-semibold {{ $current['headerText'] }}">Informations générales</h3>
            <p class="text-xs text-slate-500 mt-0.5">Parties prenantes et paramètres du dossier.</p>
        </div>
        <div class="card-body space-y-5">
            <div class="grid grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Responsable <span class="text-red-500">*</span></label>
                    <select wire:model="user_id" class="form-select">
                        <option value="">— Sélectionner —</option>
                        @foreach($responsables as $u)
                            <option value="{{ $u->id }}">{{ $u->nom_complet }} ({{ $u->initiales }})</option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Client <span class="text-red-500">*</span></label>
                    <select wire:model="client_id" class="form-select">
                        <option value="">— Sélectionner —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->nom }}@if($c->pays) ({{ $c->pays }})@endif</option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">N° de facture</label>
                    <input wire:model="numero_facture" type="text" class="form-input" placeholder="DB20/xxx">
                </div>
                <div class="form-group">
                    <label class="form-label">Référence affaire</label>
                    <input wire:model="reference_affaire" type="text" class="form-input" placeholder="190923-003AG">
                </div>
                <div class="form-group">
                    <label class="form-label">Pays de destination</label>
                    <input wire:model="pays_destination" type="text" class="form-input" placeholder="Cameroun">
                </div>
                <div class="form-group">
                    <label class="form-label">Incoterm <span class="text-red-500">*</span></label>
                    <select wire:model="incoterm" class="form-select">
                        <option value="FCA_USINE">FCA Usine</option>
                        <option value="FCA_TRANSITAIRE">FCA Transitaire</option>
                        <option value="CPT">CPT</option>
                        <option value="CFR">CFR</option>
                        <option value="EXW">EXW</option>
                        <option value="AUTRES">Autres</option>
                    </select>
                    @error('incoterm') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Lieu Incoterm</label>
                    <input wire:model="incoterm_lieu" type="text" class="form-input" placeholder="FCA Transitaire France">
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══ Step 2 : MAD Fournisseur (Étape 1) ════════════════════ --}}
    @if($currentStep === 2)
    <div class="card {{ $current['borderTop'] }}">
        <div class="card-header {{ $current['headerBg'] }}">
            <h3 class="font-semibold {{ $current['headerText'] }}">Étape 1 — Mise à disposition Fournisseur</h3>
            <p class="text-xs text-slate-500 mt-0.5">Dates de mise à disposition et réception des documents.</p>
        </div>
        <div class="card-body space-y-5">

            <div class="form-group">
                <label class="form-label">Fournisseur</label>
                <select wire:model="fournisseur_id" class="form-select">
                    <option value="">— Sélectionner —</option>
                    @foreach($fournisseurs as $f)
                        <option value="{{ $f->id }}">{{ $f->nom }}@if($f->pays) ({{ $f->pays }})@endif</option>
                    @endforeach
                </select>
                @error('fournisseur_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Date MAD prévue</label>
                    <input wire:model="mad_date_prevue" type="date" class="form-input">
                    @error('mad_date_prevue') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Date MAD réelle</label>
                    <input wire:model="mad_date_reelle" type="date" class="form-input">
                    @error('mad_date_reelle') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Date réception docs</label>
                    <input wire:model="mad_date_docs_recus" type="date" class="form-input">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <label class="flex items-center gap-3 p-4 rounded-xl border border-blue-200 bg-blue-50/50 cursor-pointer hover:bg-blue-50">
                    <input wire:model="mad_docs_recus" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                    <span class="text-sm font-medium text-slate-700">Documents reçus</span>
                </label>
                <label class="flex items-center gap-3 p-4 rounded-xl border border-blue-200 bg-blue-50/50 cursor-pointer hover:bg-blue-50">
                    <input wire:model="mad_photos_recues" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                    <span class="text-sm font-medium text-slate-700">Photos reçues</span>
                </label>
                <label class="flex items-center gap-3 p-4 rounded-xl border border-blue-200 bg-blue-50/50 cursor-pointer hover:bg-blue-50">
                    <input wire:model="mad_coc_recu" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                    <span class="text-sm font-medium text-slate-700">COC reçu</span>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label">Observations</label>
                <textarea wire:model="mad_observations" rows="3" class="form-textarea"
                          placeholder="Notes sur la mise à disposition fournisseur…"></textarea>
            </div>

            <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-blue-300 bg-blue-50 cursor-pointer">
                <input wire:model="mad_complete" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                <span class="text-sm font-semibold text-blue-800">Étape 1 complète ✓</span>
            </label>
        </div>
    </div>
    @endif

    {{-- ══ Step 3 : Facturation (Étape 2) ════════════════════════ --}}
    @if($currentStep === 3)
    <div class="card {{ $current['borderTop'] }}">
        <div class="card-header {{ $current['headerBg'] }}">
            <h3 class="font-semibold {{ $current['headerText'] }}">Étape 2 — Facturation</h3>
            <p class="text-xs text-slate-500 mt-0.5">Facture émise et réception du paiement.</p>
        </div>
        <div class="card-body space-y-5">

            <label class="flex items-center gap-3 p-4 rounded-xl border border-purple-200 bg-purple-50/50 cursor-pointer hover:bg-purple-50">
                <input wire:model="fact_emise" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                <span class="text-sm font-medium text-slate-700">Facture émise</span>
            </label>

            <hr class="border-slate-100">

            <div>
                <p class="text-sm font-medium text-slate-700 mb-3">Transitaire client</p>
                <div class="grid grid-cols-2 gap-5">
                    <div class="form-group">
                        <label class="form-label">Nom transitaire</label>
                        <input wire:model="transitaire_nom" type="text" class="form-input" placeholder="Bolloré, DHL…">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact transitaire</label>
                        <input wire:model="transitaire_contact" type="text" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Poids (kg)</label>
                        <input wire:model="poids" type="number" step="0.1" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Coût transitaire (€)</label>
                        <input wire:model="cout_transitaire" type="number" step="0.01" class="form-input">
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            <div class="grid grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Date facturation</label>
                    <input wire:model="fact_date" type="date" class="form-input">
                    @error('fact_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">N° facture interne</label>
                    <input wire:model="fact_numero" type="text" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Montant</label>
                    <input wire:model="fact_montant" type="number" step="0.01" class="form-input">
                    @error('fact_montant') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Devise</label>
                    <select wire:model="fact_devise" class="form-select">
                        <option value="EUR">EUR €</option>
                        <option value="USD">USD $</option>
                        <option value="XAF">XAF FCFA</option>
                        <option value="GBP">GBP £</option>
                    </select>
                </div>
                <label class="flex items-center gap-3 p-4 rounded-xl border border-purple-200 bg-purple-50/50 cursor-pointer hover:bg-purple-50 col-span-2">
                    <input wire:model="fact_paiement_recu" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                    <span class="text-sm font-medium text-slate-700">Paiement client reçu</span>
                </label>
                <div class="form-group">
                    <label class="form-label">Date paiement</label>
                    <input wire:model="fact_date_paiement" type="date" class="form-input">
                    @error('fact_date_paiement') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Observations</label>
                <textarea wire:model="fact_observations" rows="3" class="form-textarea"></textarea>
            </div>

            <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-purple-300 bg-purple-50 cursor-pointer">
                <input wire:model="fact_complete" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                <span class="text-sm font-semibold text-purple-800">Étape 2 complète ✓</span>
            </label>
        </div>
    </div>
    @endif

    {{-- ══ Step 4 : Transitaire (Étape 3) ════════════════════════ --}}
    @if($currentStep === 4)
    <div class="card {{ $current['borderTop'] }}">
        <div class="card-header {{ $current['headerBg'] }}">
            <h3 class="font-semibold {{ $current['headerText'] }}">Étape 3 — Coordination Transitaire</h3>
            <p class="text-xs text-slate-500 mt-0.5">Instructions envoyées et date d'enlèvement.</p>
        </div>
        <div class="card-body space-y-5">

            <label class="flex items-center gap-3 p-4 rounded-xl border border-yellow-200 bg-yellow-50/50 cursor-pointer hover:bg-yellow-50">
                <input wire:model="trans_communique" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                <span class="text-sm font-medium text-slate-700">Infos transitaire reçues du client</span>
            </label>

            <div class="grid grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Date réception infos transitaire</label>
                    <input wire:model="trans_date_reception" type="date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Date instructions envoyées</label>
                    <input wire:model="trans_date_instructions" type="date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Date enlèvement</label>
                    <input wire:model="trans_date_enlevement" type="date" class="form-input">
                    @error('trans_date_enlevement') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Observations</label>
                <textarea wire:model="trans_observations" rows="3" class="form-textarea"></textarea>
            </div>

            <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-yellow-300 bg-yellow-50 cursor-pointer">
                <input wire:model="trans_complete" type="checkbox" class="rounded {{ $current['checkAccent'] }}">
                <span class="text-sm font-semibold text-yellow-800">Étape 3 complète ✓</span>
            </label>
        </div>
    </div>
    @endif

    {{-- ══ Step 5 : Livraison + Clôture (Étape 4) ════════════════ --}}
    @if($currentStep === 5)
    <div class="space-y-5">

        {{-- Livraison — amber --}}
        <div class="card border-t-4 border-amber-500">
            <div class="card-header bg-amber-50 border-b border-amber-200">
                <h3 class="font-semibold text-amber-800">Étape 4a — Livraison Client</h3>
                <p class="text-xs text-slate-500 mt-0.5">Transport final jusqu'au client.</p>
            </div>
            <div class="card-body space-y-5">

                <label class="flex items-center gap-3 p-4 rounded-xl border border-amber-200 bg-amber-50/50 cursor-pointer hover:bg-amber-50">
                    <input wire:model="liv_applicable" type="checkbox" class="rounded accent-amber-600">
                    <span class="text-sm font-medium text-slate-700">Livraison applicable (selon incoterm)</span>
                </label>

                <div class="grid grid-cols-2 gap-5">
                    <div class="form-group">
                        <label class="form-label">Date livraison prévue</label>
                        <input wire:model="liv_date_prevue" type="date" class="form-input">
                        @error('liv_date_prevue') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date livraison réelle</label>
                        <input wire:model="liv_date_reelle" type="date" class="form-input">
                        @error('liv_date_reelle') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mode de transport</label>
                        <select wire:model="liv_mode_transport" class="form-select">
                            <option value="">— Sélectionner —</option>
                            <option value="aérien">Aérien</option>
                            <option value="maritime">Maritime</option>
                            <option value="routier">Routier</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">N° AWB / BL</label>
                        <input wire:model="liv_awb" type="text" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Observations</label>
                    <textarea wire:model="liv_observations" rows="2" class="form-textarea"></textarea>
                </div>

                <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-amber-300 bg-amber-50 cursor-pointer">
                    <input wire:model="liv_complete" type="checkbox" class="rounded accent-amber-600">
                    <span class="text-sm font-semibold text-amber-800">Livraison complète ✓</span>
                </label>
            </div>
        </div>

        {{-- Clôture — teal --}}
        <div class="card border-t-4 border-teal-500">
            <div class="card-header bg-teal-50 border-b border-teal-200">
                <h3 class="font-semibold text-teal-800">Étape 4b — Clôture (POD)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Preuve de livraison reçue — clôture définitive de l'affaire.</p>
            </div>
            <div class="card-body space-y-5">

                <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-teal-300 bg-teal-50 cursor-pointer hover:bg-teal-100">
                    <input wire:model="clot_pod_recue" type="checkbox" class="rounded accent-teal-600">
                    <span class="text-sm font-semibold text-teal-800">✓ POD reçue — clôture l'affaire</span>
                </label>

                <div class="grid grid-cols-3 gap-5">
                    <div class="form-group">
                        <label class="form-label">Date POD</label>
                        <input wire:model="clot_date_pod" type="date" class="form-input">
                        @error('clot_date_pod') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Référence POD</label>
                        <input wire:model="clot_reference" type="text" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Source POD</label>
                        <input wire:model="clot_source" type="text" class="form-input" placeholder="DHL, UPS, Bolloré…">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Observations</label>
                    <textarea wire:model="clot_observations" rows="2" class="form-textarea"></textarea>
                </div>

                <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-teal-300 bg-teal-50 cursor-pointer">
                    <input wire:model="clot_complete" type="checkbox" class="rounded accent-teal-600">
                    <span class="text-sm font-semibold text-teal-800">Clôture complète ✓</span>
                </label>
            </div>
        </div>

    </div>
    @endif

    {{-- ── Navigation ───────────────────────────────────────────── --}}
    <div class="flex items-center justify-between pt-2">
        <div>
            @if($currentStep > 1)
                <button wire:click="prevStep" type="button" class="btn-secondary">← Précédent</button>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dossiers.index') }}" class="btn-ghost">Annuler</a>
            @if($currentStep < 5)
                <button wire:click="nextStep" type="button" class="btn-secondary">Suivant →</button>
            @endif
            <button wire:click="save" type="button" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $isEdit ? 'Enregistrer les modifications' : 'Créer le dossier' }}
            </button>
        </div>
    </div>

</div>