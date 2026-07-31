<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Setting;

class Settings extends Component
{
    // Groupe : notifications
    public string $dg_email = '';

    // Groupe : MAD
    public string $validation_delai_jours = '5';

    // Groupe : général
    public string $app_nom = 'MAD Tracker';

    // Groupe : limites & abonnement (super_admin uniquement)
    public string $limite_comptes_admin = '2';
    public string $limite_comptes_total = '10';
    public string $echeance_rappel_jours = '7';

    protected function rules(): array
    {
        return [
            'dg_email'               => 'nullable|email|max:255',
            'validation_delai_jours' => 'required|integer|min:1|max:365',
            'app_nom'                => 'required|string|max:100',
        ];
    }

    protected $messages = [
        'dg_email.email'                    => 'L\'adresse email de la DG n\'est pas valide.',
        'validation_delai_jours.required'   => 'Le délai est obligatoire.',
        'validation_delai_jours.integer'    => 'Le délai doit être un nombre entier.',
        'validation_delai_jours.min'        => 'Le délai doit être d\'au moins 1 jour.',
        'app_nom.required'                  => 'Le nom de l\'application est obligatoire.',
    ];

    public function mount(): void
    {
        $this->dg_email               = Setting::get('dg_email') ?? '';
        $this->validation_delai_jours = Setting::get('validation_delai_jours', '5');
        $this->app_nom                = Setting::get('app_nom', 'MAD Tracker');

        $this->limite_comptes_admin  = Setting::get('limite_comptes_admin', '2');
        $this->limite_comptes_total  = Setting::get('limite_comptes_total', '10');
        $this->echeance_rappel_jours = Setting::get('echeance_rappel_jours', '7');
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('dg_email',               $this->dg_email ?: null);
        Setting::set('validation_delai_jours', $this->validation_delai_jours);
        Setting::set('app_nom',                $this->app_nom);

        $this->dispatch('notify', type: 'success', message: 'Paramètres enregistrés.');
    }

    /**
     * Limites d'entitlement (comptes) et rappel d'échéance : réservé au super_admin,
     * l'éditeur de la plateforme — ce n'est pas au client de fixer ses propres quotas.
     */
    public function saveEntitlements(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->validate([
            'limite_comptes_admin'  => 'required|integer|min:0|max:100',
            'limite_comptes_total'  => 'required|integer|min:1|max:1000',
            'echeance_rappel_jours' => 'required|integer|min:1|max:90',
        ]);

        Setting::set('limite_comptes_admin',  $this->limite_comptes_admin);
        Setting::set('limite_comptes_total',  $this->limite_comptes_total);
        Setting::set('echeance_rappel_jours', $this->echeance_rappel_jours);

        $this->dispatch('notify', type: 'success', message: 'Limites mises à jour.');
    }

    public function render()
    {
        return view('livewire.settings')
            ->layout('layouts.app', ['title' => 'Paramètres']);
    }
}
