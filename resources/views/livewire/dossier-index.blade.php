<div class="space-y-5">

    {{-- ── Header ───────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Dossiers</h2>
            <p class="text-sm text-slate-500 mt-0.5">Gestion et suivi des mises à disposition</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('export.dossiers') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exporter
            </a>
            <a href="{{ route('dossiers.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouveau dossier
            </a>
        </div>
    </div>

    @if($totalAlertes > 0)
    <div class="alert alert-warning">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <span><strong>{{ $totalAlertes }} dossier(s)</strong> nécessitent une attention.</span>
    </div>
    @endif

    {{-- ── Filters ──────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="md:col-span-2">
                    <input wire:model.live.debounce.300ms="search" type="search"
                           class="form-input" placeholder="Référence, affaire, client, fournisseur…">
                </div>
                <select wire:model.live="filterStatut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente">En attente</option>
                    <option value="mad_fournisseur">MAD Fournisseur</option>
                    <option value="facture">Facturé</option>
                    <option value="transitaire_ok">Transitaire OK</option>
                    <option value="enleve">Enlevé</option>
                    <option value="livre">Livré</option>
                    <option value="finalise">Finalisé</option>
                </select>
                <select wire:model.live="filterClient" class="form-select">
                    <option value="">Tous les clients</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->nom }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterAlertes" class="form-select">
                    <option value="">Toutes</option>
                    <option value="oui">Avec alertes ⚠️</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ── Table ────────────────────────────────────────────────── --}}
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th wire:click="sortBy('reference')" class="cursor-pointer select-none">
                        Référence
                        @if($sortField === 'reference')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th>Responsable</th>
                    <th>Client</th>
                    <th>Fournisseur</th>
                    <th>Incoterm</th>
                    <th wire:click="sortBy('created_at')" class="cursor-pointer select-none">
                        Date création
                        @if($sortField === 'created_at')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th>MAD Prévue</th>
                    <th>MAD Réelle</th>
                    <th>Statut</th>
                    <th>Alertes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($dossiers as $d)
                <tr @class(['bg-red-50/40' => $d->has_alerte])>
                    <td>
                        <span class="font-mono text-xs font-semibold text-brand-700">{{ $d->reference }}</span>
                        @if($d->numero_facture)
                            <br><span class="text-xs text-slate-400">{{ $d->numero_facture }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-gray">{{ $d->user->initiales ?? '?' }}</span>
                    </td>
                    <td class="font-medium">{{ $d->client->nom ?? '-' }}</td>
                    <td class="text-slate-600">{{ $d->fournisseur->nom ?? '-' }}</td>
                    <td>
                        <span class="text-xs text-slate-600">{{ $d->incoterm_label }}</span>
                    </td>
                    <td class="text-xs text-slate-500">{{ $d->created_at->format('d/m/Y') }}</td>
                    <td class="text-xs">
                        {{ $d->etapeMadFournisseur?->date_mad_prevue?->format('d/m/Y') ?? '-' }}
                    </td>
                    <td class="text-xs">
                        @if($d->etapeMadFournisseur?->date_mad_reelle)
                            {{ $d->etapeMadFournisseur->date_mad_reelle->format('d/m/Y') }}
                            @php $ecart = $d->etapeMadFournisseur->ecart_jours; @endphp
                            @if(!is_null($ecart))
                                <span @class([
                                    'ml-1 text-xs font-medium',
                                    'text-red-600' => $ecart > 0,
                                    'text-emerald-600' => $ecart <= 0,
                                ])>
                                    {{ $ecart > 0 ? "+{$ecart}j" : "{$ecart}j" }}
                                </span>
                            @endif
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $colorMap = [
                                'gray'   => 'badge-gray',
                                'blue'   => 'badge-blue',
                                'purple' => 'badge-purple',
                                'yellow' => 'badge-yellow',
                                'green'  => 'badge-green',
                                'teal'   => 'badge-green',
                            ];
                        @endphp
                        <span class="badge {{ $colorMap[$d->statut_color] ?? 'badge-gray' }}">
                            {{ $d->statut_label }}
                        </span>
                    </td>
                    <td>
                        @if($d->has_alerte)
                            <span class="text-amber-500" title="Alertes actives">⚠️</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('dossiers.show', $d->id) }}" class="btn-icon" title="Voir">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('dossiers.edit', $d->id) }}" class="btn-icon" title="Modifier">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <a href="{{ route('export.dossier.pdf', $d->id) }}" class="btn-icon" title="PDF">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </a>
                            <button wire:click="deleteDossier({{ $d->id }})"
                                    wire:confirm="Supprimer ce dossier ?"
                                    class="btn-icon text-red-400 hover:text-red-600 hover:bg-red-50" title="Supprimer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-16 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        Aucun dossier trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">
            {{ $dossiers->total() }} dossier(s)
        </p>
        <div class="pagination-wrapper">
            {{ $dossiers->links() }}
        </div>
    </div>

</div>
