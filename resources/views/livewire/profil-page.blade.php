<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <h2 class="text-xl font-semibold text-slate-900">Mon profil</h2>
        <p class="text-sm text-slate-500 mt-0.5">Vos informations et votre mot de passe.</p>
    </div>

    {{-- Informations (lecture seule — modifiables par un administrateur via Utilisateurs) --}}
    <div class="card border-t-4 border-slate-500">
        <div class="card-header bg-slate-50 border-b border-slate-200">
            <h3 class="font-semibold text-slate-800">Informations</h3>
            <p class="text-xs text-slate-500 mt-0.5">Pour modifier ces informations, contactez un administrateur.</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-slate-400 text-xs">Nom complet</p>
                    <p class="text-slate-800 font-medium">{{ $prenom }} {{ $nom }}</p>
                </div>
                <div>
                    <p class="text-slate-400 text-xs">Initiales</p>
                    <p class="text-slate-800 font-medium">{{ $initiales }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-slate-400 text-xs">Email</p>
                    <p class="text-slate-800 font-medium">{{ $email }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Changement de mot de passe --}}
    <div class="card border-t-4 border-brand-500">
        <div class="card-header bg-brand-50 border-b border-brand-200">
            <h3 class="font-semibold text-brand-800">Mot de passe</h3>
        </div>
        <div class="card-body space-y-5">
            <div class="form-group">
                <label class="form-label">Mot de passe actuel</label>
                <input wire:model="mot_de_passe_actuel" type="password" class="form-input">
                @error('mot_de_passe_actuel') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nouveau mot de passe</label>
                <input wire:model="nouveau_mot_de_passe" type="password" class="form-input">
                @error('nouveau_mot_de_passe') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Confirmer le nouveau mot de passe</label>
                <input wire:model="nouveau_mot_de_passe_confirmation" type="password" class="form-input">
            </div>
            <div class="flex justify-end">
                <button wire:click="changerMotDePasse" type="button" class="btn-primary">
                    Mettre à jour le mot de passe
                </button>
            </div>
        </div>
    </div>

</div>
