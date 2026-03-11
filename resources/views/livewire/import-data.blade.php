<div class="max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-900">Import de données</h2>
        <p class="text-sm text-slate-500 mt-0.5">Importez vos données depuis un fichier Excel (.xlsx) ou CSV</p>
    </div>

    {{-- Type selector --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">1. Choisir le type d'import</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-3 gap-3">
                @foreach([
                    ['dossiers', 'Dossiers MAD', 'Import complet avec toutes les étapes'],
                    ['clients', 'Clients', 'Nom, pays, contact'],
                    ['fournisseurs', 'Fournisseurs', 'Nom, pays, ville, contact'],
                ] as [$val, $label, $desc])
                <label @class([
                    'flex flex-col gap-1 p-4 rounded-xl border-2 cursor-pointer transition-all',
                    'border-brand-500 bg-brand-50' => $importType === $val,
                    'border-slate-200 hover:border-slate-300' => $importType !== $val,
                ])>
                    <input wire:model.live="importType" type="radio" value="{{ $val }}" class="sr-only">
                    <span class="font-semibold text-slate-800 text-sm">{{ $label }}</span>
                    <span class="text-xs text-slate-500">{{ $desc }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Template download --}}
    <div class="card border-brand-200 bg-brand-50/40">
        <div class="card-header border-brand-100">
            <h3 class="font-semibold text-brand-800">2. Télécharger le template</h3>
        </div>
        <div class="card-body">
            <p class="text-sm text-brand-700 mb-4">
                Téléchargez le fichier modèle, remplissez-le avec vos données existantes en respectant les colonnes,
                puis importez-le ci-dessous. Les colonnes en bleu sont obligatoires.
            </p>
            <div class="flex items-center gap-4">
                <a href="{{ route('import.template', ['type' => $importType]) }}"
                   class="btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Télécharger template-{{ $importType }}.xlsx
                </a>
                <span class="text-xs text-brand-600">Fichier Excel avec colonnes + exemples</span>
            </div>

            {{-- Column descriptions --}}
            <div class="mt-5 text-xs">
                @if($importType === 'dossiers')
                <p class="font-semibold text-slate-700 mb-2">Colonnes obligatoires :</p>
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach(['client', 'fournisseur'] as $col)
                        <span class="badge badge-blue">{{ $col }}</span>
                    @endforeach
                </div>
                <p class="font-semibold text-slate-700 mb-2">Notes importantes :</p>
                <ul class="space-y-1 text-slate-600 list-disc list-inside">
                    <li>Si la <strong>référence</strong> existe déjà, la ligne sera <strong>mise à jour</strong> (pas dupliquée)</li>
                    <li>Les clients et fournisseurs inexistants seront créés automatiquement</li>
                    <li>Le <strong>responsable</strong> doit correspondre aux initiales d'un utilisateur existant (ex: MSB, CA)</li>
                    <li>Dates au format <strong>dd/mm/yyyy</strong> ou <strong>yyyy-mm-dd</strong></li>
                    <li>Booléens : <strong>OUI / NON</strong> ou <strong>1 / 0</strong></li>
                    <li>Incoterms : FCA_USINE, FCA_TRANSITAIRE, CPT, CFR, EXW, AUTRES</li>
                </ul>
                @elseif($importType === 'clients')
                <p class="font-semibold text-slate-700 mb-2">Colonnes obligatoires :</p>
                <span class="badge badge-blue">nom</span>
                <p class="text-slate-600 mt-2">Si le client existe déjà (même nom), ses informations seront mises à jour.</p>
                @else
                <p class="font-semibold text-slate-700 mb-2">Colonnes obligatoires :</p>
                <span class="badge badge-blue">nom</span>
                <p class="text-slate-600 mt-2">Si le fournisseur existe déjà (même nom), ses informations seront mises à jour.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Upload --}}
    @if(!$done)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">3. Importer le fichier</h3>
        </div>
        <div class="card-body space-y-5">

            <div class="form-group">
                <label class="form-label">Fichier Excel ou CSV</label>
                <input wire:model="file" type="file" wire:key="file-input-{{ now()->timestamp }}" accept=".xlsx,.xls,.csv"
                       class="block w-full text-sm text-slate-600
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-medium
                              file:bg-brand-50 file:text-brand-700
                              hover:file:bg-brand-100 cursor-pointer">
                             
                            <div class="text-red-500">
                                <ul>
                                    @foreach ($importErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>

                <!-- @error('file') <p class="form-error">{{ $message }}</p> @enderror -->
                <p class="form-hint">Formats acceptés : .xlsx, .xls, .csv — Taille max : 10 Mo</p>
            </div>

            <div wire:loading wire:target="file" class="alert alert-info">
                <svg class="w-4 h-4 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Chargement du fichier…
            </div>

            @if($file && !$importing)
            <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                <svg class="w-8 h-8 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-800">{{ $file->getClientOriginalName() }}</p>
                    <p class="text-xs text-slate-500">{{ round($file->getSize() / 1024, 1) }} Ko</p>
                </div>
            </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="import"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60"
                        class="btn-primary btn-lg"
                        @if(!$file) disabled @endif>
                    <span wire:loading.remove wire:target="import">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </span>
                    <span wire:loading wire:target="import">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="import">Lancer l'import</span>
                    <span wire:loading wire:target="import">Import en cours…</span>
                </button>
                <a href="{{ route($importType === 'clients' ? 'clients.index' : ($importType === 'fournisseurs' ? 'fournisseurs.index' : 'dossiers.index')) }}"
                   class="btn-ghost">
                    Annuler
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Results --}}
    @if($done)
    <div class="card border-emerald-200">
        <div class="card-header border-emerald-100 bg-emerald-50">
            <h3 class="font-semibold text-emerald-800">✓ Import terminé</h3>
        </div>
        <div class="card-body space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-4 bg-emerald-50 rounded-xl">
                    <p class="text-3xl font-bold text-emerald-700">{{ $imported }}</p>
                    <p class="text-sm text-emerald-600">Importé(s)</p>
                </div>
                <div class="text-center p-4 bg-amber-50 rounded-xl">
                    <p class="text-3xl font-bold text-amber-700">{{ $skipped }}</p>
                    <p class="text-sm text-amber-600">Ignoré(s)</p>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-xl">
                    <p class="text-3xl font-bold text-red-700">{{ count($errors) }}</p>
                    <p class="text-sm text-red-600">Erreur(s)</p>
                </div>
            </div>

            @if(count($errors) > 0)
            <div class="space-y-1.5">
                <p class="text-sm font-semibold text-red-700">Détail des erreurs :</p>
                <div class="max-h-48 overflow-y-auto space-y-1">
                    @foreach($errors as $err)
                    <p class="text-xs text-red-600 bg-red-50 px-3 py-1.5 rounded">{{ $err }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <a href="{{ route($importType === 'clients' ? 'clients.index' : ($importType === 'fournisseurs' ? 'fournisseurs.index' : 'dossiers.index')) }}"
                   class="btn-primary">
                    Voir les données →
                </a>
                <button wire:click="$set('done', false)" class="btn-secondary">
                    Nouvel import
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
