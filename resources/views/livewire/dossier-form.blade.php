<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-900">{{ $title }}</h2>
        <p class="text-sm text-slate-500 mt-0.5">Remplissez les informations étape par étape</p>
    </div>

    {{-- Step indicator --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-center gap-2">
                @php
                    $steps = [
                        1 => 'Infos générales',
                        2 => 'MAD Fournisseur',
                        3 => 'Facturation',
                        4 => 'Transitaire',
                        5 => 'Livraison & Clôture',
                    ];
                @endphp
                @foreach($steps as $n => $label)
                    <button wire:click="goToStep({{ $n }})"
                            class="flex items-center gap-2 flex-1 group">
                        <span @class([
                            'w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold border-2 transition-all',
                            'bg-brand-600 border-brand-600 text-white' => $currentStep === $n,
                            'bg-emerald-500 border-emerald-500 text-white' => $currentStep > $n,
                            'bg-white border-slate-300 text-slate-400' => $currentStep < $n,
                        ])>
                            @if($currentStep > $n)✓@else{{ $n }}@endif
                        </span>
                        <span @class([
                            'text-xs font-medium hidden md:block',
                            'text-brand-700' => $currentStep === $n,
                            'text-slate-400' => $currentStep !== $n,
                        ])>{{ $label }}</span>
                    </button>
                    @if(!$loop->last)
                        <div @class([
                            'h-0.5 flex-1',
                            'bg-emerald-400' => $currentStep > $n,
                            'bg-slate-200' => $currentStep <= $n,
                        ])></div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Step 1 : Infos générales ───────────────────────────── --}}
    @if($currentStep === 1)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">Informations générales</h3>
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
                            <option value="{{ $c->id }}">{{ $c->nom }} @if($c->pays)({{ $c->pays }})@endif</option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Fournisseur <span class="text-red-500">*</span></label>
                    <select wire:model="fournisseur_id" class="form-select">
                        <option value="">— Sélectionner —</option>
                        @foreach($fournisseurs as $f)
                            <option value="{{ $f->id }}">{{ $f->nom }} @if($f->pays)({{ $f->pays }})@endif</option>
                        @endforeach
                    </select>
                    @error('fournisseur_id') <p class="form-error">{{ $message }}</p> @enderror
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
                </div>
                <div class="form-group">
                    <label class="form-label">Lieu Incoterm</label>
                    <input wire:model="incoterm_lieu" type="text" class="form-input" placeholder="FCA Transitaire France">
                </div>
            </div>

            <hr class="border-slate-100">
            <p class="text-sm font-medium text-slate-700">Transitaire client</p>
            <div class="grid grid-cols-3 gap-5">
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
    </div>
    @endif

    {{-- ── Step 2 : MAD Fournisseur ────────────────────────────── --}}
    @if($currentStep === 2)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">Étape 1 — Mise à disposition Fournisseur</h3>
        </div>
        <div class="card-body space-y-5">
            <div class="grid grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Date MAD prévue</label>
                    <input wire:model="mad_date_prevue" type="date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Date MAD réelle</label>
                    <input wire:model="mad_date_reelle" type="date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Date réception docs</label>
                    <input wire:model="mad_date_docs_recus" type="date" class="form-input">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-5">
                <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                    <input wire:model="mad_docs_recus" type="checkbox" class="rounded text-brand-600">
                    <span class="text-sm font-medium text-slate-700">Documents reçus</span>
                </label>
                <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                    <input wire:model="mad_photos_recues" type="checkbox" class="rounded text-brand-600">
                    <span class="text-sm font-medium text-slate-700">Photos reçues</span>
                </label>
                <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                    <input wire:model="mad_coc_recu" type="checkbox" class="rounded text-brand-600">
                    <span class="text-sm font-medium text-slate-700">COC reçu</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Observations</label>
                <textarea wire:model="mad_observations" rows="3" class="form-textarea"
                          placeholder="Notes sur la mise à disposition fournisseur…"></textarea>
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input wire:model="mad_complete" type="checkbox" class="rounded text-emerald-600">
                <span class="text-sm font-semibold text-slate-700">Étape 1 complète ✓</span>
            </label>
        </div>
    </div>
    @endif

    {{-- ── Step 3 : Facturation ────────────────────────────────── --}}
    @if($currentStep === 3)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">Étape 2 — Facturation</h3>
        </div>
        <div class="card-body space-y-5">
            <div class="grid grid-cols-2 gap-5">
                <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 col-span-2">
                    <input wire:model="fact_emise" type="checkbox" class="rounded text-brand-600">
                    <span class="text-sm font-medium text-slate-700">Facture émise</span>
                </label>
                <div class="form-group">
                    <label class="form-label">Date facturation</label>
                    <input wire:model="fact_date" type="date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">N° facture interne</label>
                    <input wire:model="fact_numero" type="text" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Montant</label>
                    <input wire:model="fact_montant" type="number" step="0.01" class="form-input">
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
                <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 col-span-2">
                    <input wire:model="fact_paiement_recu" type="checkbox" class="rounded text-brand-600">
                    <span class="text-sm font-medium text-slate-700">Paiement client reçu</span>
                </label>
                <div class="form-group">
                    <label class="form-label">Date paiement</label>
                    <input wire:model="fact_date_paiement" type="date" class="form-input">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Observations</label>
                <textarea wire:model="fact_observations" rows="3" class="form-textarea"></textarea>
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input wire:model="fact_complete" type="checkbox" class="rounded text-emerald-600">
                <span class="text-sm font-semibold text-slate-700">Étape 2 complète ✓</span>
            </label>
        </div>
    </div>
    @endif

    {{-- ── Step 4 : Transitaire ────────────────────────────────── --}}
    @if($currentStep === 4)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">Étape 3 — Coordination Transitaire</h3>
        </div>
        <div class="card-body space-y-5">
            <div class="grid grid-cols-2 gap-5">
                <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 col-span-2">
                    <input wire:model="trans_communique" type="checkbox" class="rounded text-brand-600">
                    <span class="text-sm font-medium text-slate-700">Infos transitaire reçues du client</span>
                </label>
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
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Observations</label>
                <textarea wire:model="trans_observations" rows="3" class="form-textarea"></textarea>
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input wire:model="trans_complete" type="checkbox" class="rounded text-emerald-600">
                <span class="text-sm font-semibold text-slate-700">Étape 3 complète ✓</span>
            </label>
        </div>
    </div>
    @endif

    {{-- ── Step 5 : Livraison + Clôture ───────────────────────── --}}
    @if($currentStep === 5)
    <div class="space-y-5">
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Étape 4 — Livraison Client</h3>
            </div>
            <div class="card-body space-y-5">
                <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                    <input wire:model="liv_applicable" type="checkbox" class="rounded text-brand-600">
                    <span class="text-sm font-medium text-slate-700">Livraison applicable (selon incoterm)</span>
                </label>
                <div class="grid grid-cols-2 gap-5">
                    <div class="form-group">
                        <label class="form-label">Date livraison prévue</label>
                        <input wire:model="liv_date_prevue" type="date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date livraison réelle</label>
                        <input wire:model="liv_date_reelle" type="date" class="form-input">
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
                <label class="flex items-center gap-3 cursor-pointer">
                    <input wire:model="liv_complete" type="checkbox" class="rounded text-emerald-600">
                    <span class="text-sm font-semibold text-slate-700">Étape 4 complète ✓</span>
                </label>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Étape 5 — Clôture (POD)</h3>
            </div>
            <div class="card-body space-y-5">
                <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-emerald-200 bg-emerald-50 cursor-pointer">
                    <input wire:model="clot_pod_recue" type="checkbox" class="rounded text-emerald-600">
                    <span class="text-sm font-semibold text-emerald-800">✓ POD reçue — clôture l'affaire</span>
                </label>
                <div class="grid grid-cols-3 gap-5">
                    <div class="form-group">
                        <label class="form-label">Date POD</label>
                        <input wire:model="clot_date_pod" type="date" class="form-input">
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
                <label class="flex items-center gap-3 cursor-pointer">
                    <input wire:model="clot_complete" type="checkbox" class="rounded text-emerald-600">
                    <span class="text-sm font-semibold text-slate-700">Étape 5 complète ✓</span>
                </label>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Navigation buttons ──────────────────────────────────── --}}
    <div class="flex items-center justify-between pt-2">
        <div>
            @if($currentStep > 1)
                <button wire:click="prevStep" class="btn-secondary">← Précédent</button>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dossiers.index') }}" class="btn-ghost">Annuler</a>
            @if($currentStep < 5)
                <button wire:click="nextStep" class="btn-primary">Suivant →</button>
            @else
                <button wire:click="save" class="btn-primary btn-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $isEdit ? 'Enregistrer les modifications' : 'Créer le dossier' }}
                </button>
            @endif
        </div>
    </div>

</div>
