<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Paramètres</h2>
            <p class="text-sm text-slate-500 mt-0.5">Configuration globale de l'application.</p>
        </div>
    </div>

    {{-- Groupe : Notifications --}}
    <div class="card border-t-4 border-indigo-500">
        <div class="card-header bg-indigo-50 border-b border-indigo-200">
            <h3 class="font-semibold text-indigo-800">Notifications par email</h3>
            <p class="text-xs text-slate-500 mt-0.5">Destinataires des alertes automatiques.</p>
        </div>
        <div class="card-body space-y-5">
            <div class="form-group">
                <label class="form-label">
                    Email Direction Générale
                    <span class="text-xs text-slate-400 font-normal ml-1">— reçoit une notification dès qu'un POD (étape 4b) est renseigné</span>
                </label>
                <input wire:model="dg_email" type="email" class="form-input" placeholder="josephine@entreprise.com">
                @error('dg_email') <p class="form-error">{{ $message }}</p> @enderror
                <p class="text-xs text-slate-400 mt-1">Laisser vide pour désactiver cette notification.</p>
            </div>
        </div>
    </div>

    {{-- Groupe : MAD Fournisseur --}}
    <div class="card border-t-4 border-blue-500">
        <div class="card-header bg-blue-50 border-b border-blue-200">
            <h3 class="font-semibold text-blue-800">Mise à disposition Fournisseur</h3>
            <p class="text-xs text-slate-500 mt-0.5">Paramètres par défaut pour les délais de validation.</p>
        </div>
        <div class="card-body space-y-5">
            <div class="form-group" style="max-width:200px">
                <label class="form-label">Délai de validation documents par défaut (jours)</label>
                <input wire:model="validation_delai_jours" type="number" min="1" max="365" class="form-input">
                @error('validation_delai_jours') <p class="form-error">{{ $message }}</p> @enderror
                <p class="text-xs text-slate-400 mt-1">Ce délai est pré-rempli sur chaque nouveau dossier mais reste modifiable.</p>
            </div>
        </div>
    </div>

    {{-- Groupe : Général --}}
    <div class="card border-t-4 border-slate-500">
        <div class="card-header bg-slate-50 border-b border-slate-200">
            <h3 class="font-semibold text-slate-800">Général</h3>
        </div>
        <div class="card-body space-y-5">
            <div class="form-group">
                <label class="form-label">Nom de l'application</label>
                <input wire:model="app_nom" type="text" class="form-input" placeholder="MAD Tracker">
                @error('app_nom') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-2">
        <button wire:click="save" type="button" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Enregistrer les paramètres
        </button>
    </div>

</div>
