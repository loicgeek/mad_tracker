<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Fournisseur;

class FournisseurIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'nom';
    public string $sortDirection = 'asc';

    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $editingId = null;

    // Form
    public string $nom = '';
    public string $pays = '';
    public string $ville = '';
    public string $contact_nom = '';
    public string $contact_email = '';
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'nom'           => 'required|min:2|max:255',
            'pays'          => 'nullable|max:100',
            'ville'         => 'nullable|max:100',
            'contact_nom'   => 'nullable|max:255',
            'contact_email' => 'nullable|email|max:255',
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
        $f = Fournisseur::findOrFail($id);
        $this->editingId     = $id;
        $this->nom           = $f->nom;
        $this->pays          = $f->pays ?? '';
        $this->ville         = $f->ville ?? '';
        $this->contact_nom   = $f->contact_nom ?? '';
        $this->contact_email = $f->contact_email ?? '';
        $this->notes         = $f->notes ?? '';
        $this->isEdit        = true;
        $this->showModal     = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'nom'           => $this->nom,
            'pays'          => $this->pays ?: null,
            'ville'         => $this->ville ?: null,
            'contact_nom'   => $this->contact_nom ?: null,
            'contact_email' => $this->contact_email ?: null,
            'notes'         => $this->notes ?: null,
        ];

        if ($this->isEdit) {
            Fournisseur::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Fournisseur mis à jour.');
        } else {
            Fournisseur::create($data);
            $this->dispatch('notify', type: 'success', message: 'Fournisseur créé.');
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $f = Fournisseur::withCount('dossiers')->findOrFail($id);
        if ($f->dossiers_count > 0) {
            $this->dispatch('notify', type: 'error', message: "Impossible : {$f->dossiers_count} dossier(s) liés.");
            return;
        }
        $f->delete();
        $this->dispatch('notify', type: 'success', message: 'Fournisseur supprimé.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->nom = $this->pays = $this->ville = '';
        $this->contact_nom = $this->contact_email = $this->notes = '';
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        $fournisseurs = Fournisseur::withCount('dossiers')
            ->when($this->search, fn($q) => $q
                ->where('nom', 'like', "%{$this->search}%")
                ->orWhere('pays', 'like', "%{$this->search}%")
                ->orWhere('ville', 'like', "%{$this->search}%"))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(20);

        return view('livewire.fournisseur-index', compact('fournisseurs'))
            ->layout('layouts.app', ['title' => 'Fournisseurs']);
    }
}
