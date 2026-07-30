<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersIndex extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal state
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $editingId = null;

    // Form fields
    public string $nom = '';
    public string $prenom = '';
    public string $initiales = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'gestionnaire';

    public const LIMITE_COMPTES_ADMINISTRATIF = 4;

    protected function rules(): array
    {
        return [
            'nom'       => 'required|min:2|max:255',
            'prenom'    => 'required|min:2|max:255',
            'initiales' => ['required', 'max:10', Rule::unique('users', 'initiales')->ignore($this->editingId)],
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password'  => $this->isEdit ? 'nullable|min:8' : 'required|min:8',
            'role'      => ['required', Rule::in($this->rolesAssignables())],
        ];
    }

    /**
     * Rôles qu'un administratif peut attribuer sont limités ; un super_admin peut tout attribuer.
     */
    private function rolesAssignables(): array
    {
        return auth()->user()->isSuperAdmin()
            ? array_keys(User::ROLES)
            : ['admin', 'gestionnaire', 'lecteur'];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            $count = auth()->user()->comptesCrees()->count();
            if ($count >= self::LIMITE_COMPTES_ADMINISTRATIF) {
                $this->dispatch('notify', type: 'error', message: 'Limite de 4 comptes créés atteinte.');
                return;
            }
        }

        $this->resetForm();
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = $this->baseQuery()->findOrFail($id);
        $this->editingId = $id;
        $this->nom       = $user->nom;
        $this->prenom    = $user->prenom;
        $this->initiales = $user->initiales;
        $this->email     = $user->email;
        $this->password  = '';
        $this->role      = $user->role;
        $this->isEdit    = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'nom'       => $this->nom,
            'prenom'    => $this->prenom,
            'initiales' => strtoupper($this->initiales),
            'email'     => $this->email,
            'role'      => $this->role,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEdit) {
            $this->baseQuery()->findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Compte mis à jour.');
        } else {
            if (! auth()->user()->isSuperAdmin()
                && auth()->user()->comptesCrees()->count() >= self::LIMITE_COMPTES_ADMINISTRATIF) {
                $this->dispatch('notify', type: 'error', message: 'Limite de 4 comptes créés atteinte.');
                return;
            }

            $data['created_by'] = auth()->id();
            $data['actif']      = true;
            User::create($data);
            $this->dispatch('notify', type: 'success', message: 'Compte créé.');
        }

        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->nom = $this->prenom = $this->initiales = $this->email = $this->password = '';
        $this->role = 'gestionnaire';
        $this->editingId = null;
        $this->resetValidation();
    }

    /**
     * super_admin voit tous les comptes ; administratif ne voit que lui-même
     * et les comptes qu'il a créés.
     */
    private function baseQuery()
    {
        $query = User::query();

        if (! auth()->user()->isSuperAdmin()) {
            $query->where(fn ($q) => $q->where('id', auth()->id())->orWhere('created_by', auth()->id()));
        }

        return $query;
    }

    public function render()
    {
        $users = $this->baseQuery()
            ->when($this->search, fn ($q) => $q->where('nom', 'like', "%{$this->search}%")
                ->orWhere('prenom', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->orderBy('nom')
            ->paginate(20);

        return view('livewire.users-index', compact('users'))
            ->layout('layouts.app', ['title' => 'Utilisateurs']);
    }
}
