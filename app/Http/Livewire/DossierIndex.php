<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Dossier;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\User;

class DossierIndex extends Component
{
    use WithPagination;

    // Filters
    public string $search            = '';
    public string $filterStatut      = '';
    public string $filterClient      = '';
    public string $filterResponsable = '';
    public string $filterIncoterm    = '';
    public string $filterAlertes     = '';
    public string $sortField         = 'created_at';
    public string $sortDirection     = 'desc';
    public int    $perPage           = 25;

    protected $queryString = [
        'search'            => ['except' => ''],
        'filterStatut'      => ['except' => ''],
        'filterClient'      => ['except' => ''],
        'filterResponsable' => ['except' => ''],
        'filterIncoterm'    => ['except' => ''],
        'filterAlertes'     => ['except' => ''],
        'sortField'         => ['except' => 'created_at'],
        'sortDirection'     => ['except' => 'desc'],
    ];

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatut(): void  { $this->resetPage(); }
    public function updatingFilterClient(): void  { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    public function deleteDossier(int $id): void
    {
        $dossier = Dossier::findOrFail($id);
        $dossier->delete();
        $this->dispatch('notify', type: 'success', message: 'Dossier supprimé.');
    }

    public function render()
    {
        $query = Dossier::query()
            ->with([
                'client',
                'user',
                'fournisseur',
                'etapeMadFournisseur',
                'etapeFacturation',
                'etapeTransitaire',
                'etapeLivraison',
                'etapeCloture',
            ])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                  ->orWhere('reference_affaire', 'like', "%{$this->search}%")
                  ->orWhere('numero_facture', 'like', "%{$this->search}%")
                  ->orWhereHas('client', fn($q) => $q->where('nom', 'like', "%{$this->search}%"))
                  ->orWhereHas('fournisseur', fn($q) => $q->where('nom', 'like', "%{$this->search}%"));
            }))
            ->when($this->filterStatut,      fn($q) => $q->where('statut', $this->filterStatut))
            ->when($this->filterClient,      fn($q) => $q->where('client_id', $this->filterClient))
            ->when($this->filterResponsable, fn($q) => $q->where('user_id', $this->filterResponsable))
            ->when($this->filterIncoterm,    fn($q) => $q->where('incoterm', $this->filterIncoterm))
            ->when($this->filterAlertes === 'oui', fn($q) => $q->withAlertes())
            ->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.dossier-index', [
            'dossiers'     => $query->paginate($this->perPage),
            'clients'      => Client::orderBy('nom')->get(),
            'responsables' => User::orderBy('nom')->get(),
            'totalAlertes' => Dossier::withAlertes()->count(),
        ])->layout('layouts.app', ['title' => 'Dossiers']);
    }
}