<div class="space-y-5 max-w-3xl">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Paiements &amp; abonnement</h2>
            <p class="text-sm text-slate-500 mt-0.5">Abonnement de la plateforme (cette instance) — géré par l'éditeur</p>
        </div>
        <button wire:click="openPaiementModal" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Enregistrer un paiement
        </button>
    </div>

    {{-- Statut --}}
    <div class="card">
        <div class="card-body flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    @if($actif)
                        <span class="badge badge-green">Plateforme active</span>
                    @else
                        <span class="badge badge-red">Plateforme désactivée</span>
                    @endif
                </div>
                @if($dateEcheance)
                    @php $jours = now()->startOfDay()->diffInDays($dateEcheance->copy()->startOfDay(), false); @endphp
                    <p class="text-sm text-slate-600 mt-2">
                        Échéance : <span class="font-medium">{{ $dateEcheance->format('d/m/Y') }}</span>
                        @if($jours < 0)
                            <span class="badge badge-red ml-2">Expirée depuis {{ abs($jours) }}j</span>
                        @elseif($jours <= 7)
                            <span class="badge badge-yellow ml-2">Expire dans {{ $jours }}j</span>
                        @else
                            <span class="badge badge-blue ml-2">{{ $jours }}j restants</span>
                        @endif
                    </p>
                @else
                    <p class="text-sm text-slate-400 mt-2">Aucune échéance définie — enregistrez un premier paiement.</p>
                @endif
            </div>
            <button wire:click="toggleActif"
                    wire:confirm="{{ $actif ? 'Désactiver la plateforme pour tous les utilisateurs (sauf super_admin) ?' : 'Réactiver la plateforme ?' }}"
                    class="btn-secondary">
                {{ $actif ? 'Désactiver manuellement' : 'Activer manuellement' }}
            </button>
        </div>
    </div>

    {{-- Historique --}}
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Mois couverts</th>
                    <th>Échéance résultante</th>
                    <th>Enregistré par</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paiements as $p)
                <tr>
                    <td class="text-slate-600">{{ $p->date_paiement->format('d/m/Y') }}</td>
                    <td class="font-semibold text-slate-800">{{ number_format($p->montant, 2) }} {{ $p->devise }}</td>
                    <td class="text-slate-600">{{ $p->methode }}</td>
                    <td class="text-slate-600">{{ $p->mois_couverts }}</td>
                    <td class="text-slate-600">{{ $p->date_echeance_resultante->format('d/m/Y') }}</td>
                    <td class="text-xs text-slate-500">{{ $p->saisiPar?->nom_complet ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-slate-400">
                        Aucun paiement enregistré
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
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
