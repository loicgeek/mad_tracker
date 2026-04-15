<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Transporteur;

class TransporteurIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'nom';
    public string $sortDirection = 'asc';

    // Modal state
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $editingId = null;

    // Form fields
    public string $nom = '';
    public string $pays = '';
    public string $contact_nom = '';
    public string $contact_email = '';
    public string $contact_phone = '';
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'nom'           => 'required|min:2|max:255',
            'pays'          => 'nullable|max:100',
            'contact_nom'   => 'nullable|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|max:50',
            'notes'         => 'nullable|max:2000',
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $t = Transporteur::findOrFail($id);
        $this->editingId     = $id;
        $this->nom           = $t->nom;
        $this->pays          = $t->pays ?? '';
        $this->contact_nom   = $t->contact_nom ?? '';
        $this->contact_email = $t->contact_email ?? '';
        $this->contact_phone = $t->contact_phone ?? '';
        $this->notes         = $t->notes ?? '';
        $this->isEdit        = true;
        $this->showModal     = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'nom'           => $this->nom,
            'pays'          => $this->pays ?: null,
            'contact_nom'   => $this->contact_nom ?: null,
            'contact_email' => $this->contact_email ?: null,
            'contact_phone' => $this->contact_phone ?: null,
            'notes'         => $this->notes ?: null,
        ];

        if ($this->isEdit) {
            Transporteur::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Transporteur mis à jour.');
        } else {
            Transporteur::create($data);
            $this->dispatch('notify', type: 'success', message: 'Transporteur créé.');
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $t = Transporteur::withCount('dossiers')->findOrFail($id);
        if ($t->dossiers_count > 0) {
            $this->dispatch('notify', type: 'error', message: "Impossible : {$t->dossiers_count} dossier(s) liés.");
            return;
        }
        $t->delete();
        $this->dispatch('notify', type: 'success', message: 'Transporteur supprimé.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->nom = $this->pays = $this->contact_nom = '';
        $this->contact_email = $this->contact_phone = $this->notes = '';
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        $transporteurs = Transporteur::withCount('dossiers')
            ->when($this->search, fn($q) => $q->where('nom', 'like', "%{$this->search}%")
                ->orWhere('pays', 'like', "%{$this->search}%"))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(20);

        return view('livewire.transporteur-index', compact('transporteurs'))
            ->layout('layouts.app', ['title' => 'Transporteurs']);
    }
}
