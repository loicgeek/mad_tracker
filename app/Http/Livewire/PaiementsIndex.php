<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Paiement;
use Carbon\Carbon;

class PaiementsIndex extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal "enregistrer un paiement"
    public bool $showPaiementModal = false;
    public ?int $paiementUserId = null;
    public string $montant = '';
    public string $devise = 'XAF';
    public string $methode = 'virement';
    public string $date_paiement = '';
    public int $mois_couverts = 1;
    public string $notes = '';

    // Historique
    public ?int $historiqueUserId = null;

    protected function rules(): array
    {
        return [
            'montant'       => 'required|numeric|min:0',
            'devise'        => 'required|in:XAF,EUR,USD,GBP',
            'methode'       => 'required|in:virement,especes,mobile_money,autre',
            'date_paiement' => 'required|date',
            'mois_couverts' => 'required|integer|in:1,3,6,12',
            'notes'         => 'nullable|max:2000',
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openPaiementModal(int $userId): void
    {
        $this->paiementUserId = $userId;
        $this->montant = '';
        $this->devise = 'XAF';
        $this->methode = 'virement';
        $this->date_paiement = now()->format('Y-m-d');
        $this->mois_couverts = 1;
        $this->notes = '';
        $this->resetValidation();
        $this->showPaiementModal = true;
    }

    public function closePaiementModal(): void
    {
        $this->showPaiementModal = false;
        $this->paiementUserId = null;
    }

    public function enregistrerPaiement(): void
    {
        $this->validate();

        $user = User::findOrFail($this->paiementUserId);

        $base = $user->date_echeance && $user->date_echeance->isFuture()
            ? $user->date_echeance
            : now();
        $nouvelleEcheance = Carbon::parse($base)->addMonths($this->mois_couverts);

        Paiement::create([
            'user_id'                  => $user->id,
            'montant'                  => $this->montant,
            'devise'                   => $this->devise,
            'methode'                  => $this->methode,
            'date_paiement'            => $this->date_paiement,
            'mois_couverts'            => $this->mois_couverts,
            'date_echeance_resultante' => $nouvelleEcheance,
            'notes'                    => $this->notes ?: null,
            'saisi_par_id'             => auth()->id(),
        ]);

        $user->update([
            'date_echeance' => $nouvelleEcheance,
            'actif'         => true,
        ]);

        $this->dispatch('notify', type: 'success', message: 'Paiement enregistré, compte activé jusqu\'au ' . $nouvelleEcheance->format('d/m/Y') . '.');
        $this->closePaiementModal();
    }

    public function toggleActif(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['actif' => ! $user->actif]);

        $this->dispatch('notify', type: 'success', message: $user->actif ? 'Compte activé.' : 'Compte désactivé.');
    }

    public function toggleHistorique(int $userId): void
    {
        $this->historiqueUserId = $this->historiqueUserId === $userId ? null : $userId;
    }

    public function render()
    {
        $users = User::where('role', '!=', 'super_admin')
            ->when($this->search, fn ($q) => $q->where('nom', 'like', "%{$this->search}%")
                ->orWhere('prenom', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->with(['paiements' => fn ($q) => $q->latest('date_paiement')])
            ->orderBy('nom')
            ->paginate(20);

        return view('livewire.paiements-index', compact('users'))
            ->layout('layouts.app', ['title' => 'Paiements']);
    }
}
