<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Dossier;
use App\Models\Observation;
use Illuminate\Support\Facades\Auth;

class DossierShow extends Component
{
    public Dossier $dossier;
    public string $newObservation = '';
    public string $newObservationEtape = 'general';
    public string $newObservationType = 'info';

    public function mount(int $id): void
    {
        $this->dossier = Dossier::with([
            'client', 'user', 'fournisseur',
            'etapeMadFournisseur', 'etapeFacturation',
            'etapeTransitaire', 'etapeLivraison', 'etapeCloture',
            'observations.user',
        ])->findOrFail($id);
    }

    public function addObservation(): void
    {
        $this->validate([
            'newObservation' => 'required|min:3|max:2000',
            'newObservationEtape' => 'required',
            'newObservationType' => 'required',
        ]);

        Observation::create([
            'dossier_id' => $this->dossier->id,
            'user_id'    => Auth::id(),
            'etape'      => $this->newObservationEtape,
            'contenu'    => $this->newObservation,
            'type'       => $this->newObservationType,
        ]);

        $this->newObservation = '';
        $this->dossier->refresh()->load('observations.user');
        $this->dispatch('notify', type: 'success', message: 'Observation ajoutée.');
    }

    public function deleteObservation(int $id): void
    {
        Observation::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
        $this->dossier->refresh()->load('observations.user');
    }

    public function render()
    {
        return view('livewire.dossier-show', [
            'dossier' => $this->dossier,
        ])->layout('layouts.app', ['title' => $this->dossier->reference]);
    }
}
