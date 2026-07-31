<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Utilisateurs</h2>
            <p class="text-sm text-slate-500 mt-0.5">
                Comptes : {{ $comptesTotalActuels }}/{{ $limiteComptesTotal }}
                · dont administrateurs : {{ $comptesAdminActuels }}/{{ $limiteComptesAdmin }}
                @if(!auth()->user()->isSuperAdmin())
                    <span class="text-slate-400">(limites fixées par l'éditeur de la plateforme)</span>
                @endif
            </p>
        </div>
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau compte
        </button>
    </div>

    {{-- Search --}}
    <div class="card">
        <div class="card-body">
            <input wire:model.live.debounce.300ms="search" type="search"
                   class="form-input max-w-sm" placeholder="Nom, prénom, email…">
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Initiales</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $u->nom_complet }}</td>
                    <td class="text-slate-600">{{ $u->initiales }}</td>
                    <td class="text-xs text-slate-500">{{ $u->email }}</td>
                    <td class="text-slate-600">{{ \App\Models\User::ROLES[$u->role] ?? $u->role }}</td>
                    <td>
                        @if($u->actif)
                            <span class="badge badge-green">Actif</span>
                        @else
                            <span class="badge badge-gray">Inactif</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEdit({{ $u->id }})" class="btn-icon" title="Modifier">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-slate-400">
                        Aucun utilisateur trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ $users->total() }} utilisateur(s)</p>
        <div class="pagination-wrapper">{{ $users->links() }}</div>
    </div>

    {{-- ── Modal ──────────────────────────────────────────────────── --}}
    @if($showModal)
    <div class="modal-backdrop" wire:click="closeModal"></div>
    <div class="modal-panel" x-trap.noscroll="true">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h3 class="font-semibold text-slate-900">
                    {{ $isEdit ? 'Modifier le compte' : 'Nouveau compte' }}
                </h3>
                <button wire:click="closeModal" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body space-y-4">
                @if($entitlementError)
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                    {{ $entitlementError }}
                </div>
                @endif
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Prénom <span class="text-red-500">*</span></label>
                        <input wire:model="prenom" type="text" class="form-input">
                        @error('prenom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom <span class="text-red-500">*</span></label>
                        <input wire:model="nom" type="text" class="form-input">
                        @error('nom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Initiales <span class="text-red-500">*</span></label>
                        <input wire:model="initiales" type="text" class="form-input" maxlength="10" placeholder="HO">
                        @error('initiales') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rôle <span class="text-red-500">*</span></label>
                        <select wire:model.live="role" class="form-select">
                            @foreach(\App\Models\User::ROLES as $value => $label)
                                @if(auth()->user()->isSuperAdmin() || in_array($value, ['admin','gestionnaire','lecteur']))
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('role') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Email <span class="text-red-500">*</span></label>
                        <input wire:model="email" type="email" class="form-input">
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Mot de passe @if(!$isEdit)<span class="text-red-500">*</span>@else (laisser vide pour ne pas changer)@endif</label>
                        <input wire:model="password" type="password" class="form-input">
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button wire:click="closeModal" class="btn-secondary">Annuler</button>
                <button wire:click="save" class="btn-primary" @if($entitlementError) disabled @endif>
                    {{ $isEdit ? 'Enregistrer' : 'Créer' }}
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
