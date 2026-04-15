<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Transporteurs</h2>
            <p class="text-sm text-slate-500 mt-0.5">Gestion des transitaires et transporteurs</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau transporteur
        </button>
    </div>

    {{-- Search --}}
    <div class="card">
        <div class="card-body">
            <input wire:model.live.debounce.300ms="search" type="search"
                   class="form-input max-w-sm" placeholder="Nom, pays…">
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th wire:click="sortBy('nom')" class="cursor-pointer">
                        Nom @if($sortField==='nom'){{ $sortDirection==='asc'?'↑':'↓' }}@endif
                    </th>
                    <th wire:click="sortBy('pays')" class="cursor-pointer">
                        Pays @if($sortField==='pays'){{ $sortDirection==='asc'?'↑':'↓' }}@endif
                    </th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th class="text-right">Dossiers</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transporteurs as $t)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $t->nom }}</td>
                    <td class="text-slate-600">{{ $t->pays ?? '—' }}</td>
                    <td class="text-slate-600">{{ $t->contact_nom ?? '—' }}</td>
                    <td class="text-xs text-slate-500">{{ $t->contact_email ?? '—' }}</td>
                    <td class="text-xs text-slate-500">{{ $t->contact_phone ?? '—' }}</td>
                    <td class="text-right">
                        <span class="badge {{ $t->dossiers_count > 0 ? 'badge-blue' : 'badge-gray' }}">
                            {{ $t->dossiers_count }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEdit({{ $t->id }})" class="btn-icon" title="Modifier">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="delete({{ $t->id }})"
                                    wire:confirm="Supprimer ce transporteur ?"
                                    class="btn-icon text-red-400 hover:text-red-600 hover:bg-red-50" title="Supprimer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-slate-400">
                        Aucun transporteur trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ $transporteurs->total() }} transporteur(s)</p>
        <div class="pagination-wrapper">{{ $transporteurs->links() }}</div>
    </div>

    {{-- ── Modal ──────────────────────────────────────────────────── --}}
    @if($showModal)
    <div class="modal-backdrop" wire:click="closeModal"></div>
    <div class="modal-panel" x-trap.noscroll="true">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h3 class="font-semibold text-slate-900">
                    {{ $isEdit ? 'Modifier le transporteur' : 'Nouveau transporteur' }}
                </h3>
                <button wire:click="closeModal" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group col-span-2">
                        <label class="form-label">Nom <span class="text-red-500">*</span></label>
                        <input wire:model="nom" type="text" class="form-input" placeholder="DHL, Bolloré…">
                        @error('nom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pays</label>
                        <input wire:model="pays" type="text" class="form-input" placeholder="France">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact</label>
                        <input wire:model="contact_nom" type="text" class="form-input" placeholder="Jean Dupont">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input wire:model="contact_email" type="email" class="form-input">
                        @error('contact_email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input wire:model="contact_phone" type="text" class="form-input">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Notes</label>
                        <textarea wire:model="notes" rows="3" class="form-textarea"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button wire:click="closeModal" class="btn-secondary">Annuler</button>
                <button wire:click="save" class="btn-primary">
                    {{ $isEdit ? 'Enregistrer' : 'Créer' }}
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
