<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Dossier;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\User;
use App\Models\EtapeMadFournisseur;
use App\Models\EtapeFacturation;
use App\Models\EtapeTransitaire;
use App\Models\EtapeLivraison;
use App\Models\EtapeCloture;
use App\Models\Transporteur;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DossierForm extends Component
{
    public ?Dossier $dossier = null;
    public bool $isEdit = false;
    public int $currentStep = 1;

    // ── Step 1 : Informations générales ──
    public string $reference = '';
    public int    $user_id = 0;
    public int    $client_id = 0;
    public ?int   $fournisseur_id = null;
    public string $reference_affaire = '';
    public string $pays_destination = '';
    public string $incoterm = 'FCA_USINE';
    public string $incoterm_lieu = '';
    public string $categorie = '';
    public string $type_commande = '';

    // ── Step 1 suite : MAD Fournisseur (fusionné avec infos générales) ──
    public string $mad_date_prevue = '';
    public string $mad_date_fournisseur = '';
    public string $mad_date_reelle = '';
    public bool   $mad_docs_recus = false;
    public bool   $mad_photos_recues = false;
    public string $mad_date_validation_document = '';
    public string $mad_date_demande_validation = '';
    public string $mad_date_reception_validation = '';
    public int    $mad_delai_validation_jours = 5;
    public string $mad_observations = '';
    public bool   $mad_complete = false;

    // ── Step 2 : Facturation ──
    public bool   $fact_emise = false;
    public string $fact_date = '';
    public string $fact_numero_facture = '';
    public string $fact_date_echeance = '';
    public bool   $fact_coc_coo = false;
    public string $fact_montant_client = '';
    public string $fact_devise_client = 'EUR';
    public string $fact_montant_fournisseur = '';
    public string $fact_devise_fournisseur = 'EUR';
    public string $fact_taux_change = '';
    public string $fact_observations = '';
    public bool   $fact_complete = false;

    // ── Step 5 (4c) : Validation facture client + paiement ──
    public bool   $fact_validation_client = false;
    public string $fact_date_validation_facture = '';
    public bool   $fact_paiement_recu = false;
    public string $fact_date_paiement = '';

    // ── Step 2 suite : Transporteur (dans facturation) ──
    public ?int   $transporteur_id = null;
    public string $transitaire_nom = '';
    public string $transitaire_contact = '';
    public string $poids = '';
    public string $cout_transitaire = '';
    public string $cout_reel = '';

    // ── Step 3 : Transitaire ──
    public bool   $trans_communique = false;
    public string $trans_date_reception = '';
    public string $trans_date_instructions = '';
    public string $trans_date_enlevement = '';
    public string $trans_observations = '';
    public bool   $trans_complete = false;

    // ── Step 4 : Livraison ──
    public string $liv_date_prevue = '';
    public string $liv_date_reelle = '';
    public string $liv_mode_transport = '';
    public string $liv_awb = '';
    public string $liv_motif_retard = '';
    public bool   $liv_applicable = true;
    public string $liv_observations = '';
    public bool   $liv_complete = false;

    // ── Step 4 suite : Clôture ──
    public bool   $clot_pod_recue = false;
    public string $clot_date_pod = '';
    public string $clot_reference = '';
    public string $clot_source = '';
    public string $clot_observations = '';
    public bool   $clot_complete = false;

    protected function rules(): array
    {
        $uniqueRef = 'unique:dossiers,reference,' . ($this->isEdit ? ($this->dossier?->id ?? 'NULL') : 'NULL');
        return [
            'reference'                   => "required|string|max:255|{$uniqueRef}",
            'user_id'                     => 'required|exists:users,id',
            'client_id'                   => 'required|exists:clients,id',
            'fournisseur_id'              => 'nullable|exists:fournisseurs,id',
            'incoterm'                    => 'required|in:FCA_USINE,FCA_TRANSITAIRE,CPT,CFR,EXW,AUTRES',
            'type_commande'               => 'nullable|in:standard,projet',
            'transporteur_id'             => 'nullable|exists:transporteurs,id',
            'cout_reel'                   => 'nullable|numeric|min:0',
            'liv_motif_retard'            => 'nullable|string|max:500',
            'mad_date_prevue'              => 'nullable|date',
            'mad_date_fournisseur'         => 'nullable|date',
            'mad_date_reelle'              => 'nullable|date',
            'mad_date_validation_document' => 'nullable|date',
            'mad_date_demande_validation'  => 'nullable|date',
            'mad_date_reception_validation'=> 'nullable|date',
            'mad_delai_validation_jours'   => 'nullable|integer|min:1|max:365',
            'fact_date'                    => 'nullable|date',
            'fact_date_echeance'           => 'nullable|date',
            'fact_date_validation_facture' => 'nullable|date',
            'fact_date_paiement'           => 'nullable|date',
            'fact_montant_client'          => 'nullable|numeric',
            'fact_montant_fournisseur'     => 'nullable|numeric',
            'fact_taux_change'             => 'nullable|numeric|min:0',
            'trans_date_enlevement'       => 'nullable|date',
            'liv_date_prevue'             => 'nullable|date',
            'liv_date_reelle'             => 'nullable|date',
            'clot_date_pod'               => 'nullable|date',
        ];
    }

    public function mount(int $id = 0): void
    {
        $this->user_id = Auth::id() ?? 1;

        if ($id) {
            $this->dossier = Dossier::with([
                'etapeMadFournisseur','etapeFacturation',
                'etapeTransitaire','etapeLivraison','etapeCloture',
            ])->findOrFail($id);
            $this->isEdit = true;
            $this->fillFromModel();
        }
    }

    private function fillFromModel(): void
    {
        $d = $this->dossier;
        $this->reference         = $d->reference;
        $this->user_id           = $d->user_id;
        $this->client_id         = $d->client_id;
        $this->fournisseur_id    = $d->fournisseur_id;
        $this->reference_affaire = $d->reference_affaire ?? '';
        $this->pays_destination  = $d->pays_destination ?? '';
        $this->incoterm          = $d->incoterm;
        $this->incoterm_lieu     = $d->incoterm_lieu ?? '';
        $this->categorie         = $d->categorie ?? '';
        $this->type_commande     = $d->type_commande ?? '';
        $this->transporteur_id   = $d->transporteur_id;
        $this->transitaire_nom   = $d->transitaire_nom ?? '';
        $this->transitaire_contact = $d->transitaire_contact ?? '';
        $this->poids             = $d->poids ?? '';
        $this->cout_transitaire  = $d->cout_transitaire ?? '';
        $this->cout_reel         = $d->cout_reel ?? '';

        if ($m = $d->etapeMadFournisseur) {
            $this->mad_date_prevue               = $m->date_mad_prevue?->format('Y-m-d') ?? '';
            $this->mad_date_fournisseur          = $m->date_mad_fournisseur?->format('Y-m-d') ?? '';
            $this->mad_date_reelle               = $m->date_mad_reelle?->format('Y-m-d') ?? '';
            $this->mad_docs_recus                = $m->docs_recus;
            $this->mad_photos_recues             = $m->photos_recues;
            $this->mad_date_validation_document  = $m->date_validation_document?->format('Y-m-d') ?? '';
            $this->mad_date_demande_validation   = $m->date_demande_validation?->format('Y-m-d') ?? '';
            $this->mad_date_reception_validation = $m->date_reception_validation?->format('Y-m-d') ?? '';
            $this->mad_delai_validation_jours    = $m->delai_validation_jours ?? 5;
            $this->mad_observations              = $m->observations ?? '';
            $this->mad_complete                  = $m->complete;
        }

        if ($f = $d->etapeFacturation) {
            $this->fact_emise                 = $f->facture_emise;
            $this->fact_date                  = $f->date_facturation?->format('Y-m-d') ?? '';
            $this->fact_numero_facture        = $f->numero_facture ?? '';
            $this->fact_date_echeance         = $f->date_echeance_facture?->format('Y-m-d') ?? '';
            $this->fact_coc_coo               = $f->coc_coo;
            $this->fact_montant_client        = $f->montant_client ?? ($f->montant ?? '');
            $this->fact_devise_client         = $f->devise_client ?? ($f->devise ?? 'EUR');
            $this->fact_montant_fournisseur   = $f->montant_fournisseur ?? '';
            $this->fact_devise_fournisseur    = $f->devise_fournisseur ?? 'EUR';
            $this->fact_taux_change           = $f->taux_change ?? '';
            $this->fact_validation_client     = $f->validation_facture_client;
            $this->fact_date_validation_facture = $f->date_validation_facture?->format('Y-m-d') ?? '';
            $this->fact_paiement_recu         = $f->paiement_recu;
            $this->fact_date_paiement         = $f->date_paiement?->format('Y-m-d') ?? '';
            $this->fact_observations          = $f->observations ?? '';
            $this->fact_complete              = $f->complete;
        }

        if ($t = $d->etapeTransitaire) {
            $this->trans_communique        = $t->transitaire_communique;
            $this->trans_date_reception    = $t->date_reception_infos_transitaire?->format('Y-m-d') ?? '';
            $this->trans_date_instructions = $t->date_instructions_envoyees?->format('Y-m-d') ?? '';
            $this->trans_date_enlevement   = $t->date_enlevement?->format('Y-m-d') ?? '';
            $this->trans_observations      = $t->observations ?? '';
            $this->trans_complete          = $t->complete;
        }

        if ($l = $d->etapeLivraison) {
            $this->liv_date_prevue    = $l->date_livraison_prevue?->format('Y-m-d') ?? '';
            $this->liv_date_reelle    = $l->date_livraison_reelle?->format('Y-m-d') ?? '';
            $this->liv_mode_transport = $l->mode_transport ?? '';
            $this->liv_awb            = $l->awb_bl_numero ?? '';
            $this->liv_motif_retard   = $l->motif_retard ?? '';
            $this->liv_applicable     = $l->applicable;
            $this->liv_observations   = $l->observations ?? '';
            $this->liv_complete       = $l->complete;
        }

        if ($c = $d->etapeCloture) {
            $this->clot_pod_recue  = $c->pod_recue;
            $this->clot_date_pod   = $c->date_pod?->format('Y-m-d') ?? '';
            $this->clot_reference  = $c->pod_reference ?? '';
            $this->clot_source     = $c->pod_source ?? '';
            $this->clot_observations = $c->observations ?? '';
            $this->clot_complete   = $c->complete;
        }
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $data = [
                'reference'        => $this->reference,
                'user_id'          => $this->user_id,
                'client_id'        => $this->client_id,
                'fournisseur_id'   => $this->fournisseur_id ?: null,
                'reference_affaire'=> $this->reference_affaire ?: null,
                'pays_destination' => $this->pays_destination ?: null,
                'incoterm'         => $this->incoterm,
                'incoterm_lieu'    => $this->incoterm_lieu ?: null,
                'categorie'        => $this->categorie ?: null,
                'type_commande'    => $this->type_commande ?: null,
                'transporteur_id'  => $this->transporteur_id ?: null,
                'transitaire_nom'  => $this->transitaire_nom ?: null,
                'transitaire_contact' => $this->transitaire_contact ?: null,
                'poids'            => $this->poids ?: null,
                'cout_transitaire' => $this->cout_transitaire ?: null,
                'cout_reel'        => $this->cout_reel ?: null,
            ];

            if ($this->isEdit) {
                $this->dossier->update($data);
                $d = $this->dossier;
            } else {
                $d = Dossier::create($data);
                // Notifier le responsable de l'attribution
                if ($d->user) {
                    $d->user->notify(new \App\Notifications\DossierAttribue(
                        dossierId: $d->id,
                        reference: $d->reference,
                        clientNom: $d->client?->nom ?? '—',
                    ));
                }
            }

            $isNew = ! $this->isEdit;

            // Étape 1 — MAD Fournisseur
            $prevMad = $d->etapeMadFournisseur?->date_reception_validation;
            $madData = [
                'date_mad_prevue'           => $this->mad_date_prevue ?: null,
                'date_mad_fournisseur'      => $this->mad_date_fournisseur ?: null,
                'date_mad_reelle'           => $this->mad_date_reelle ?: null,
                'docs_recus'                => $this->mad_docs_recus,
                'photos_recues'             => $this->mad_photos_recues,
                'date_validation_document'  => $this->mad_date_validation_document ?: null,
                'date_demande_validation'   => $this->mad_date_demande_validation ?: null,
                'date_reception_validation' => $this->mad_date_reception_validation ?: null,
                'delai_validation_jours'    => $this->mad_delai_validation_jours ?: 5,
                'observations'              => $this->mad_observations ?: null,
                'complete'                  => $this->mad_complete,
            ];
            $d->etapeMadFournisseur()->updateOrCreate(['dossier_id' => $d->id], $madData);

            // Notifier si la date de réception de validation vient d'être saisie
            if ($this->mad_date_reception_validation && ! $prevMad && $d->user) {
                $d->user->notify(new \App\Notifications\ValidationDocumentRecu(
                    dossierId:     $d->id,
                    reference:     $d->reference,
                    clientNom:     $d->client?->nom ?? '—',
                    dateReception: now()->parse($this->mad_date_reception_validation)->format('d/m/Y'),
                ));
            }

            // Étape 2 — Facturation
            $d->etapeFacturation()->updateOrCreate(['dossier_id' => $d->id], [
                'facture_emise'             => $this->fact_emise,
                'date_facturation'          => $this->fact_date ?: null,
                'numero_facture'            => $this->fact_numero_facture ?: null,
                'date_echeance_facture'     => $this->fact_date_echeance ?: null,
                'coc_coo'                   => $this->fact_coc_coo,
                'montant_client'            => $this->fact_montant_client ?: null,
                'devise_client'             => $this->fact_devise_client,
                'montant_fournisseur'       => $this->fact_montant_fournisseur ?: null,
                'devise_fournisseur'        => $this->fact_devise_fournisseur,
                'taux_change'               => $this->fact_taux_change ?: null,
                'validation_facture_client' => $this->fact_validation_client,
                'date_validation_facture'   => $this->fact_date_validation_facture ?: null,
                'paiement_recu'             => $this->fact_paiement_recu,
                'date_paiement'             => $this->fact_date_paiement ?: null,
                'observations'              => $this->fact_observations ?: null,
                'complete'                  => $this->fact_complete,
            ]);

            // Calcul temps traitement
            $tempsTraitement = null;
            if ($this->mad_date_reelle && $this->trans_date_enlevement) {
                $tempsTraitement = now()->parse($this->mad_date_reelle)
                    ->diffInDays(now()->parse($this->trans_date_enlevement));
            }

            // Étape 3 — Transitaire
            $d->etapeTransitaire()->updateOrCreate(['dossier_id' => $d->id], [
                'transitaire_communique'           => $this->trans_communique,
                'date_reception_infos_transitaire' => $this->trans_date_reception ?: null,
                'date_instructions_envoyees'       => $this->trans_date_instructions ?: null,
                'date_enlevement'                  => $this->trans_date_enlevement ?: null,
                'temps_traitement_jours'           => $tempsTraitement,
                'observations'                     => $this->trans_observations ?: null,
                'complete'                         => $this->trans_complete,
            ]);

            // Étape 4 — Livraison
            $d->etapeLivraison()->updateOrCreate(['dossier_id' => $d->id], [
                'date_livraison_prevue'  => $this->liv_date_prevue ?: null,
                'date_livraison_reelle'  => $this->liv_date_reelle ?: null,
                'mode_transport'         => $this->liv_mode_transport ?: null,
                'awb_bl_numero'          => $this->liv_awb ?: null,
                'motif_retard'           => $this->liv_motif_retard ?: null,
                'applicable'             => $this->liv_applicable,
                'observations'           => $this->liv_observations ?: null,
                'complete'               => $this->liv_complete,
            ]);

            // Étape 5 — Clôture
            $d->etapeCloture()->updateOrCreate(['dossier_id' => $d->id], [
                'pod_recue'     => $this->clot_pod_recue,
                'date_pod'      => $this->clot_date_pod ?: null,
                'pod_reference' => $this->clot_reference ?: null,
                'pod_source'    => $this->clot_source ?: null,
                'observations'  => $this->clot_observations ?: null,
                'complete'      => $this->clot_complete,
            ]);

            $d->refresh();
            $d->recalculerStatut();
        });

        $this->dispatch('notify', type: 'success', message: $this->isEdit ? 'Dossier mis à jour.' : 'Dossier créé.');
        $this->redirect(route('dossiers.index'));
    }

    public function nextStep(): void
    {
        if ($this->currentStep < 5) $this->currentStep++;
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) $this->currentStep--;
    }

    public function goToStep(int $step): void
    {
        $this->currentStep = $step;
    }

    public function render()
    {
        return view('livewire.dossier-form', [
            'clients'        => Client::orderBy('nom')->get(),
            'fournisseurs'   => Fournisseur::orderBy('nom')->get(),
            'responsables'   => User::where('actif', true)->orderBy('nom')->get(),
            'transporteurs'  => Transporteur::orderBy('nom')->get(),
            'title'          => $this->isEdit ? 'Modifier le dossier' : 'Nouveau dossier',
        ])->layout('layouts.app', ['title' => $this->isEdit ? 'Modifier dossier' : 'Nouveau dossier']);
    }
}
