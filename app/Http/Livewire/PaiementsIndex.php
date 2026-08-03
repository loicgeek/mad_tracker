<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Paiement;
use App\Support\Abonnement;
use Carbon\Carbon;

class PaiementsIndex extends Component
{
    // Modal "enregistrer un paiement"
    public bool $showPaiementModal = false;
    public string $montant = '';
    public string $devise = 'XAF';
    public string $methode = 'virement';
    public string $date_paiement = '';
    public int $mois_couverts = 1;
    public string $notes = '';

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

    public function openPaiementModal(): void
    {
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
    }

    public function enregistrerPaiement(): void
    {
        $this->validate();

        $dateEcheanceActuelle = Abonnement::dateEcheance();
        $base = $dateEcheanceActuelle && $dateEcheanceActuelle->isFuture() ? $dateEcheanceActuelle : now();
        $nouvelleEcheance = Carbon::parse($base)->addMonths($this->mois_couverts);

        Paiement::create([
            'montant'                  => $this->montant,
            'devise'                   => $this->devise,
            'methode'                  => $this->methode,
            'date_paiement'            => $this->date_paiement,
            'mois_couverts'            => $this->mois_couverts,
            'date_echeance_resultante' => $nouvelleEcheance,
            'notes'                    => $this->notes ?: null,
            'saisi_par_id'             => auth()->id(),
        ]);

        Abonnement::activerJusquau($nouvelleEcheance);

        $this->dispatch('notify', type: 'success', message: 'Paiement enregistré, plateforme active jusqu\'au ' . $nouvelleEcheance->format('d/m/Y') . '.');
        $this->closePaiementModal();
    }

    public function toggleActif(): void
    {
        $nouvelEtat = ! Abonnement::actif();
        Abonnement::definirActif($nouvelEtat);

        $this->dispatch('notify', type: 'success', message: $nouvelEtat ? 'Plateforme activée.' : 'Plateforme désactivée.');
    }

    public function render()
    {
        $paiements = Paiement::with('saisiPar')->latest('date_paiement')->get();

        return view('livewire.paiements-index', [
            'paiements'    => $paiements,
            'actif'        => Abonnement::actif(),
            'dateEcheance' => Abonnement::dateEcheance(),
        ])->layout('layouts.app', ['title' => 'Paiements']);
    }
}
