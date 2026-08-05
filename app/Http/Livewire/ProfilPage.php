<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfilPage extends Component
{
    public string $nom = '';
    public string $prenom = '';
    public string $initiales = '';
    public string $email = '';

    public string $mot_de_passe_actuel = '';
    public string $nouveau_mot_de_passe = '';
    public string $nouveau_mot_de_passe_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->nom       = $user->nom;
        $this->prenom    = $user->prenom;
        $this->initiales = $user->initiales;
        $this->email     = $user->email;
    }

    public function changerMotDePasse(): void
    {
        $this->validate([
            'mot_de_passe_actuel'                => 'required',
            'nouveau_mot_de_passe'                => 'required|min:8|confirmed',
        ], [
            'nouveau_mot_de_passe.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'nouveau_mot_de_passe.min'       => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
        ]);

        $user = auth()->user();

        if (! Hash::check($this->mot_de_passe_actuel, $user->password)) {
            $this->addError('mot_de_passe_actuel', 'Mot de passe actuel incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->nouveau_mot_de_passe)]);

        $this->mot_de_passe_actuel = '';
        $this->nouveau_mot_de_passe = '';
        $this->nouveau_mot_de_passe_confirmation = '';
        $this->resetValidation();

        $this->dispatch('notify', type: 'success', message: 'Mot de passe mis à jour.');
    }

    public function render()
    {
        return view('livewire.profil-page')
            ->layout('layouts.app', ['title' => 'Mon profil']);
    }
}
