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
    public string $editingOriginalRole = '';

    // Form fields
    public string $nom = '';
    public string $prenom = '';
    public string $initiales = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'gestionnaire';
    public string $entitlementError = '';

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
     * Rôles qu'un admin peut attribuer sont limités ; un super_admin peut tout attribuer.
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
     * Vérifie l'entitlement pour l'état actuel du formulaire (création ou modification).
     * - Le plafond total ne s'applique qu'à une création (un edit ne change pas le nombre de comptes).
     * - Le plafond "comptes admin" s'applique dès que le compte DEVIENT admin : à la création
     *   avec ce rôle, ou en modification si le rôle d'origine n'était pas déjà admin.
     * Retourne un message d'erreur si dépassé, sinon null.
     */
    private function verifierEntitlement(): ?string
    {
        if (! $this->isEdit && $this->comptesTotalActuels() >= $this->limiteComptesTotal()) {
            return "Limite de {$this->limiteComptesTotal()} comptes atteinte pour cet abonnement.";
        }

        $devientAdmin = $this->role === 'admin' && $this->editingOriginalRole !== 'admin';
        if ($devientAdmin && $this->comptesAdminActuels() >= $this->limiteComptesAdmin()) {
            return "Limite de {$this->limiteComptesAdmin()} comptes administrateur atteinte pour cet abonnement.";
        }

        return null;
    }

    public function updatingSearch(): void { $this->resetPage(); }

    /**
     * Retour immédiat dans le formulaire dès que le rôle choisi dépasse l'entitlement,
     * sans attendre la soumission.
     */
    public function updatedRole(): void
    {
        $this->entitlementError = $this->verifierEntitlement() ?? '';
    }

    public function openCreate(): void
    {
        // Le plafond total peut déjà être connu avant même de choisir un rôle ;
        // le plafond spécifique aux comptes admin ne peut être vérifié qu'une fois
        // le rôle choisi dans le formulaire (cf. updatedRole()).
        if ($this->comptesTotalActuels() >= $this->limiteComptesTotal()) {
            $this->dispatch('notify', type: 'error', message: "Limite de {$this->limiteComptesTotal()} comptes atteinte pour cet abonnement.");
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
        $this->editingOriginalRole = $user->role;
        $this->isEdit    = true;
        $this->entitlementError = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $erreur = $this->verifierEntitlement();
        if ($erreur) {
            $this->entitlementError = $erreur;
            return;
        }
        $this->entitlementError = '';

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

    public function toggleActif(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'Vous ne pouvez pas désactiver votre propre compte.');
            return;
        }

        $user = $this->baseQuery()->findOrFail($id);
        $user->update(['actif' => ! $user->actif]);

        $this->dispatch('notify', type: 'success', message: $user->actif ? 'Compte activé.' : 'Compte désactivé.');
    }

    private function resetForm(): void
    {
        $this->nom = $this->prenom = $this->initiales = $this->email = $this->password = '';
        $this->role = 'gestionnaire';
        $this->editingId = null;
        $this->editingOriginalRole = '';
        $this->entitlementError = '';
        $this->resetValidation();
    }

    /**
     * super_admin (éditeur de la plateforme) voit tous les comptes, y compris les autres
     * super_admin. L'admin gère l'ensemble des comptes de son équipe (tout sauf
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
