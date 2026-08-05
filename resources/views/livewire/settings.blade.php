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

    {{-- Groupe : Limites & abonnement (réservé au super administrateur / éditeur) --}}
    @if(auth()->user()->isSuperAdmin())
    <div class="card border-t-4 border-amber-500">
        <div class="card-header bg-amber-50 border-b border-amber-200">
            <h3 class="font-semibold text-amber-800">Limites &amp; abonnement</h3>
            <p class="text-xs text-slate-500 mt-0.5">Réservé à l'éditeur de la plateforme — définit ce que le client peut créer sous son abonnement actuel.</p>
        </div>
        <div class="card-body space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Comptes administrateur max</label>
                    <input wire:model="limite_comptes_admin" type="number" min="0" max="100" class="form-input">
                    @error('limite_comptes_admin') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Comptes utilisateur max (total)</label>
                    <input wire:model="limite_comptes_total" type="number" min="1" max="1000" class="form-input">
                    @error('limite_comptes_total') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Rappel avant échéance d'un compte (jours)</label>
                    <input wire:model="echeance_rappel_jours" type="number" min="1" max="90" class="form-input" style="max-width:200px">
                    @error('echeance_rappel_jours') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button wire:click="saveEntitlements" type="button" class="btn-primary">
                    Enregistrer les limites
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Groupe : Général (pas pour les admins simples) --}}
    @if($this->peutGererGeneral())
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
            <div class="flex justify-end">
                <button wire:click="saveGeneral" type="button" class="btn-secondary">
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="flex justify-end pt-2">
        <button wire:click="save" type="button" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Enregistrer les paramètres
        </button>
    </div>

</div>
