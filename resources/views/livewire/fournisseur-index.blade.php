<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Fournisseurs</h2>
            <p class="text-sm text-slate-500 mt-0.5">Gestion du panel fournisseurs</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('import') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Importer
            </a>
            <button wire:click="openCreate" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouveau fournisseur
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <input wire:model.live.debounce.300ms="search" type="search"
                   class="form-input max-w-sm" placeholder="Nom, pays, ville…">
        </div>
    </div>

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
                    <th>Ville</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th class="text-right">Dossiers</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($fournisseurs as $f)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $f->nom }}</td>
                    <td class="text-slate-600">{{ $f->pays ?? '—' }}</td>
                    <td class="text-slate-600">{{ $f->ville ?? '—' }}</td>
                    <td class="text-slate-600">{{ $f->contact_nom ?? '—' }}</td>
                    <td class="text-xs text-slate-500">{{ $f->contact_email ?? '—' }}</td>
                    <td class="text-right">
                        @if($f->dossiers_count > 0)
                            <a href="{{ route('dossiers.index', ['filterFournisseur' => $f->id]) }}"
                               class="badge badge-blue hover:opacity-80 transition-opacity">
                                {{ $f->dossiers_count }}
                            </a>
                        @else
                            <span class="badge badge-gray">0</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEdit({{ $f->id }})" class="btn-icon">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="delete({{ $f->id }})"
                                    wire:confirm="Supprimer ce fournisseur ?"
                                    class="btn-icon text-red-400 hover:text-red-600 hover:bg-red-50">
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
                    <td colspan="7" class="text-center py-12 text-slate-400">Aucun fournisseur trouvé</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ $fournisseurs->total() }} fournisseur(s)</p>
        <div class="pagination-wrapper">{{ $fournisseurs->links() }}</div>
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="modal-backdrop" wire:click="closeModal"></div>
    <div class="modal-panel" x-trap.noscroll="true">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h3 class="font-semibold text-slate-900">
                    {{ $isEdit ? 'Modifier le fournisseur' : 'Nouveau fournisseur' }}
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
                        <input wire:model="nom" type="text" class="form-input" placeholder="Masoneilan AF-Sud">
                        @error('nom') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pays</label>
                        <input wire:model="pays" type="text" class="form-input" placeholder="France">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ville</label>
                        <input wire:model="ville" type="text" class="form-input" placeholder="Paris">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact</label>
                        <input wire:model="contact_nom" type="text" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input wire:model="contact_email" type="email" class="form-input">
                        @error('contact_email') <p class="form-error">{{ $message }}</p> @enderror
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
