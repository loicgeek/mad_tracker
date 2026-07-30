<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Paiements &amp; échéances</h2>
            <p class="text-sm text-slate-500 mt-0.5">Activation des comptes et suivi des abonnements</p>
        </div>
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
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Échéance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $u->nom_complet }}</td>
                    <td class="text-slate-600">{{ \App\Models\User::ROLES[$u->role] ?? $u->role }}</td>
                    <td>
                        @if($u->actif)
                            <span class="badge badge-green">Actif</span>
                        @else
                            <span class="badge badge-gray">Inactif</span>
                        @endif
                    </td>
                    <td>
                        @if(!$u->date_echeance)
                            <span class="text-slate-400">—</span>
                        @else
                            @php $jours = now()->startOfDay()->diffInDays($u->date_echeance->startOfDay(), false); @endphp
                            <div class="text-slate-600">{{ $u->date_echeance->format('d/m/Y') }}</div>
                            @if($jours < 0)
                                <span class="badge badge-red">Expiré depuis {{ abs($jours) }}j</span>
                            @elseif($jours <= 7)
                                <span class="badge badge-yellow">Expire dans {{ $jours }}j</span>
                            @else
                                <span class="badge badge-blue">{{ $jours }}j restants</span>
                            @endif
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <button wire:click="openPaiementModal({{ $u->id }})" class="btn-secondary !py-1 !px-2 text-xs">
                                Enregistrer un paiement
                            </button>
                            <button wire:click="toggleActif({{ $u->id }})"
                                    wire:confirm="{{ $u->actif ? 'Désactiver ce compte ?' : 'Activer ce compte ?' }}"
                                    class="btn-icon" title="{{ $u->actif ? 'Désactiver' : 'Activer' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                </svg>
                            </button>
                            <button wire:click="toggleHistorique({{ $u->id }})" class="btn-icon" title="Historique">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @if($historiqueUserId === $u->id)
                <tr>
                    <td colspan="5" class="bg-slate-50">
                        @if($u->paiements->isEmpty())
                            <p class="text-sm text-slate-400 py-2">Aucun paiement enregistré.</p>
                        @else
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-slate-500">
                                        <th class="text-left py-1">Date</th>
                                        <th class="text-left py-1">Montant</th>
                                        <th class="text-left py-1">Méthode</th>
                                        <th class="text-left py-1">Mois couverts</th>
                                        <th class="text-left py-1">Échéance résultante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($u->paiements as $p)
                                    <tr>
                                        <td class="py-1">{{ $p->date_paiement->format('d/m/Y') }}</td>
                                        <td class="py-1">{{ number_format($p->montant, 2) }} {{ $p->devise }}</td>
                                        <td class="py-1">{{ $p->methode }}</td>
                                        <td class="py-1">{{ $p->mois_couverts }}</td>
                                        <td class="py-1">{{ $p->date_echeance_resultante->format('d/m/Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-slate-400">
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

    {{-- ── Modal paiement ─────────────────────────────────────────── --}}
    @if($showPaiementModal)
    <div class="modal-backdrop" wire:click="closePaiementModal"></div>
    <div class="modal-panel" x-trap.noscroll="true">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h3 class="font-semibold text-slate-900">Enregistrer un paiement</h3>
                <button wire:click="closePaiementModal" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Montant <span class="text-red-500">*</span></label>
                        <input wire:model="montant" type="number" step="0.01" class="form-input">
                        @error('montant') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Devise</label>
                        <select wire:model="devise" class="form-select">
                            <option value="XAF">XAF</option>
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Méthode</label>
                        <select wire:model="methode" class="form-select">
                            <option value="virement">Virement</option>
                            <option value="especes">Espèces</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date du paiement <span class="text-red-500">*</span></label>
                        <input wire:model="date_paiement" type="date" class="form-input">
                        @error('date_paiement') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Durée couverte</label>
                        <select wire:model="mois_couverts" class="form-select">
                            <option value="1">1 mois</option>
                            <option value="3">3 mois</option>
                            <option value="6">6 mois</option>
                            <option value="12">12 mois</option>
                        </select>
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Notes</label>
                        <textarea wire:model="notes" rows="2" class="form-textarea"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button wire:click="closePaiementModal" class="btn-secondary">Annuler</button>
                <button wire:click="enregistrerPaiement" class="btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
    @endif

</div>
