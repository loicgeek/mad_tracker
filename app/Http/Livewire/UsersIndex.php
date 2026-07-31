<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Setting;
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

    /**
     * Limites d'entitlement fixées par le super_admin (éditeur de la plateforme) —
     * cf. /parametres, carte "Limites & abonnement".
     */
    private function limiteComptesAdmin(): int
    {
        return (int) Setting::get('limite_comptes_admin', 2);
    }

    private function limiteComptesTotal(): int
    {
        return (int) Setting::get('limite_comptes_total', 10);
    }

    private function comptesAdminActuels(): int
    {
        return User::where('role', 'admin')->count();
    }

    private function comptesTotalActuels(): int
    {
        return User::where('role', '!=', 'super_admin')->count();
    }

    /**
     * Vérifie l'entitlement avant création. Retourne un message d'erreur si dépassé, sinon null.
     */
    private function verifierEntitlement(string $roleVoulu): ?string
    {
        if ($this->comptesTotalActuels() >= $this->limiteComptesTotal()) {
            return "Limite de {$this->limiteComptesTotal()} comptes atteinte pour cet abonnement.";
        }
        if ($roleVoulu === 'admin' && $this->comptesAdminActuels() >= $this->limiteComptesAdmin()) {
            return "Limite de {$this->limiteComptesAdmin()} comptes administrateur atteinte pour cet abonnement.";
        }
        return null;
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        if ($erreur = $this->verifierEntitlement($this->role)) {
            $this->dispatch('notify', type: 'error', message: $erreur);
            return;
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
            if ($erreur = $this->verifierEntitlement($this->role)) {
                $this->dispatch('notify', type: 'error', message: $erreur);
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
     * super_admin (éditeur de la plateforme) voit tous les comptes, y compris les autres
     * super_admin. L'administratif gère l'ensemble des comptes de son équipe (tout sauf
     * super_admin), sous les limites d'entitlement fixées par le super_admin.
     */
    private function baseQuery()
    {
        $query = User::query();

        if (! auth()->user()->isSuperAdmin()) {
            $query->where('role', '!=', 'super_admin');
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
            ->layout('layouts.app', ['title' => 'Utilisateurs'])
            ->with([
                'comptesTotalActuels' => $this->comptesTotalActuels(),
                'limiteComptesTotal'  => $this->limiteComptesTotal(),
                'comptesAdminActuels' => $this->comptesAdminActuels(),
                'limiteComptesAdmin'  => $this->limiteComptesAdmin(),
            ]);
    }
}
