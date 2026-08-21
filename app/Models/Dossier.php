<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use App\Models\Transporteur;

class Dossier extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'reference', 'user_id', 'client_id', 'fournisseur_id',
        'numero_facture', 'reference_affaire', 'pays_destination',
        'incoterm', 'incoterm_lieu', 'date_promise_client', 'categorie',
        'type_commande', 'transporteur_id',
        'transitaire_nom', 'transitaire_contact',
        'poids', 'dimensions', 'cout_transitaire', 'cout_reel',
        'statut',
        'alerte_retard_mad', 'alerte_facture_manquante',
        'alerte_transitaire_manquant', 'alerte_pod_manquante',
        'alerte_livraison_depassee', 'alerte_cout_reel_manquant',
        'alerte_mad_reelle_manquante', 'alerte_validation_documents_manquante',
        'alerte_co_coc_manquant', 'alerte_transitaire_infos_manquantes',
    ];

    protected $casts = [
        'date_promise_client'          => 'date',
        'poids'                        => 'decimal:2',
        'cout_transitaire'             => 'decimal:2',
        'cout_reel'                    => 'decimal:2',
        'alerte_retard_mad'            => 'boolean',
        'alerte_facture_manquante'     => 'boolean',
        'alerte_transitaire_manquant'  => 'boolean',
        'alerte_pod_manquante'         => 'boolean',
        'alerte_livraison_depassee'    => 'boolean',
        'alerte_cout_reel_manquant'    => 'boolean',
        'alerte_mad_reelle_manquante'            => 'boolean',
        'alerte_validation_documents_manquante'  => 'boolean',
        'alerte_co_coc_manquant'                 => 'boolean',
        'alerte_transitaire_infos_manquantes'    => 'boolean',
    ];

    /**
     * Les alertes ne s'appliquent qu'aux dossiers créés à partir de cette date
     * (demande client explicite : pas de bruit rétroactif sur l'historique repris).
     */
    public const ALERTES_CUTOFF = '2026-09-01';

    // ── Relations ──────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class)->withDefault([
            'nom' => 'Non défini',
        ]);
    }

    public function transporteur(): BelongsTo
    {
        return $this->belongsTo(Transporteur::class)->withDefault([
            'nom' => 'Non défini',
        ]);
    }

    public function etapeMadFournisseur(): HasOne
    {
        return $this->hasOne(EtapeMadFournisseur::class);
    }

    public function etapeFacturation(): HasOne
    {
        return $this->hasOne(EtapeFacturation::class);
    }

    public function etapeTransitaire(): HasOne
    {
        return $this->hasOne(EtapeTransitaire::class);
    }

    public function etapeLivraison(): HasOne
    {
        return $this->hasOne(EtapeLivraison::class);
    }

    public function etapeCloture(): HasOne
    {
        return $this->hasOne(EtapeCloture::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class)->latest();
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'en_attente'      => 'En attente',
            'mad_fournisseur' => 'MAD Fournisseur',
            'facture'         => 'Facturé',
            'transitaire_ok'  => 'Transitaire OK',
            'enleve'          => 'Enlevé',
            'livre'           => 'Livré',
            'finalise'        => 'Finalisé',
            default           => $this->statut,
        };
    }

    /**
     * Step-aligned color token.
     * Consumed by Dashboard::loadAlertes() and any blade that needs a raw
     * color name (e.g. to build dynamic CSS classes).
     *
     * Mapping mirrors the 4 étapes shown in the form / index dot track:
     *   Étape 1 — MAD Fournisseur  → blue
     *   Étape 2 — Facturation       → purple
     *   Étape 3 — Transitaire       → yellow   (transitaire_ok + enleve)
     *   Étape 4 — Livraison/Clôture → amber (livre) · teal (finalise)
     */
    public function getStatutColorAttribute(): string
    {
        return match($this->statut) {
            'mad_fournisseur'           => 'blue',
            'facture'                   => 'purple',
            'transitaire_ok', 'enleve'  => 'yellow',
            'livre'                     => 'amber',
            'finalise'                  => 'teal',
            default                     => 'gray',   // en_attente
        };
    }

    /**
     * Ready-to-use Tailwind badge class.
     * Use `$dossier->statut_badge` instead of building the match() in every blade.
     */
    public function getStatutBadgeAttribute(): string
    {
        return match($this->statut) {
            'mad_fournisseur'           => 'badge-blue',
            'facture'                   => 'badge-purple',
            'transitaire_ok', 'enleve'  => 'badge-yellow',
            'livre'                     => 'badge-amber',
            'finalise'                  => 'badge-teal',
            default                     => 'badge-gray',
        };
    }

    public function getActionSuggereeAttribute(): string
    {
        return match($this->statut) {
            'en_attente'      => 'Saisir la date MAD fournisseur prévue',
            'mad_fournisseur' => 'Vérifier les documents reçus et émettre la facture',
            'facture'         => 'Communiquer les instructions au transitaire',
            'transitaire_ok'  => 'Confirmer la date d\'enlèvement',
            'enleve'          => 'Suivre l\'expédition et mettre à jour la livraison prévue',
            'livre'           => 'Récupérer le POD et clôturer le dossier',
            'finalise'        => 'Dossier clôturé — vérifier le paiement',
            default           => 'Mettre à jour le dossier',
        };
    }

    public function getHasAlerteAttribute(): bool
    {
        return $this->alerte_retard_mad
            || $this->alerte_facture_manquante
            || $this->alerte_transitaire_manquant
            || $this->alerte_pod_manquante
            || $this->alerte_livraison_depassee
            || $this->alerte_cout_reel_manquant
            || $this->alerte_mad_reelle_manquante
            || $this->alerte_validation_documents_manquante
            || $this->alerte_co_coc_manquant
            || $this->alerte_transitaire_infos_manquantes;
    }

    public function getIncotermLabelAttribute(): string
    {
        return match($this->incoterm) {
            'FCA_USINE'       => 'FCA Usine',
            'FCA_TRANSITAIRE' => 'FCA Transitaire',
            'CPT'             => 'CPT',
            'CFR'             => 'CFR',
            'EXW'             => 'EXW',
            'AUTRES'          => 'Autres',
            default           => $this->incoterm,
        };
    }

    // ── Business Methods ───────────────────────────────────────────

    public function refreshAlertes(): void
    {
        // Pas de bruit sur l'historique repris : les alertes (toutes, existantes
        // et nouvelles) ne s'appliquent qu'aux dossiers créés à partir du cutoff.
        if (! $this->created_at || $this->created_at->lt(self::ALERTES_CUTOFF)) {
            $this->alerte_retard_mad = false;
            $this->alerte_facture_manquante = false;
            $this->alerte_transitaire_manquant = false;
            $this->alerte_pod_manquante = false;
            $this->alerte_livraison_depassee = false;
            $this->alerte_cout_reel_manquant = false;
            $this->alerte_mad_reelle_manquante = false;
            $this->alerte_validation_documents_manquante = false;
            $this->alerte_co_coc_manquant = false;
            $this->alerte_transitaire_infos_manquantes = false;
            $this->saveQuietly();
            return;
        }

        $today = now()->toDateString();

        $mad  = $this->etapeMadFournisseur;
        $fact = $this->etapeFacturation;
        $trans= $this->etapeTransitaire;
        $liv  = $this->etapeLivraison;
        $clot = $this->etapeCloture;

        $this->alerte_retard_mad = $mad
            && $mad->date_mad_prevue
            && ! $mad->date_mad_reelle
            && $mad->date_mad_prevue < $today;

        $this->alerte_facture_manquante = $mad
            && $mad->complete
            && $fact
            && ! $fact->facture_emise;

        // Transitaire manquant = facture émise mais pas de transporteur renseigné
        $this->alerte_transitaire_manquant = $fact
            && $fact->facture_emise
            && ! $this->transporteur_id;

        $this->alerte_livraison_depassee = $liv
            && $liv->date_livraison_prevue
            && ! $liv->date_livraison_reelle
            && $liv->date_livraison_prevue < $today;

        // POD manquante : toujours vide 5 jours après la date de livraison réelle
        $this->alerte_pod_manquante = $liv
            && $liv->date_livraison_reelle
            && (! $clot || ! $clot->pod_recue)
            && $liv->date_livraison_reelle->copy()->addDays(5)->isPast();

        // Coût réel manquant = transporteur renseigné mais coût réel absent
        $this->alerte_cout_reel_manquant = $this->transporteur_id
            && (! $this->cout_reel || $this->cout_reel == 0);

        // MAD réelle manquante : toujours vide 2 jours après la date MAD fournisseur
        $this->alerte_mad_reelle_manquante = $mad
            && $mad->date_mad_fournisseur
            && ! $mad->date_mad_reelle
            && $mad->date_mad_fournisseur->copy()->addDays(2)->isPast();

        // Validation documents : document pas validé, délai attendu dépassé
        // depuis la date de demande de validation aux commerciaux.
        $this->alerte_validation_documents_manquante = $mad
            && $mad->date_demande_validation
            && ! $mad->date_validation_document
            && $mad->date_demande_validation->copy()->addDays($mad->delai_validation_jours ?? 5)->isPast();

        // CO/COC reçu : pas les deux cases cochées, 2 jours après la date de facture
        $this->alerte_co_coc_manquant = $fact
            && $fact->date_facturation
            && ! ($fact->co_recu && $fact->coc_recu)
            && $fact->date_facturation->copy()->addDays(2)->isPast();

        // Infos transitaire : toujours vide 5 jours après la date de facturation
        $this->alerte_transitaire_infos_manquantes = $fact
            && $fact->date_facturation
            && (! $trans || ! $trans->date_reception_infos_transitaire)
            && $fact->date_facturation->copy()->addDays(5)->isPast();

        $this->saveQuietly();
    }

    public function recalculerStatut(): void
    {
        // $this->statut peut être null en mémoire juste après un create() qui
        // n'a pas fourni 'statut' explicitement : la valeur par défaut ('en_attente')
        // n'existe alors qu'en base tant que le modèle n'a pas été rechargé.
        $ancienStatut = $this->statut ?? 'en_attente';

        if ($this->etapeCloture?->complete) {
            $this->statut = 'finalise';
        } elseif ($this->etapeLivraison?->complete) {
            $this->statut = 'livre';
        } elseif ($this->etapeTransitaire?->date_enlevement) {
            $this->statut = 'enleve';
        } elseif ($this->etapeTransitaire?->complete) {
            $this->statut = 'transitaire_ok';
        } elseif ($this->etapeFacturation?->complete) {
            $this->statut = 'facture';
        } else {
            $this->statut = 'mad_fournisseur';
        }

        $this->saveQuietly();
        $this->refreshAlertes();

        if ($this->statut !== $ancienStatut && $this->user) {
            \App\Support\SafeNotifier::send($this->user, new \App\Notifications\DossierStatutChanged(
                dossierId:          $this->id,
                reference:          $this->reference_affaire,
                clientNom:          $this->client?->nom ?? '—',
                ancienStatut:       $ancienStatut,
                nouveauStatut:      $this->statut,
                nouveauStatutLabel: $this->statut_label,
                actionSuggeree:     $this->action_suggeree,
            ));
        }

        // Mail DG lorsque POD (4b) est renseignée pour la première fois
        $clot = $this->etapeCloture;
        if ($clot && $clot->pod_recue && $this->statut === 'finalise' && $ancienStatut !== 'finalise') {
            $dgEmail = \App\Models\Setting::get('dg_email');
            if ($dgEmail) {
                \App\Support\SafeNotifier::send(
                    \Illuminate\Support\Facades\Notification::route('mail', $dgEmail),
                    new \App\Notifications\PodRecueDG(
                        dossierId: $this->id,
                        reference: $this->reference_affaire,
                        clientNom: $this->client?->nom ?? '—',
                        datePod:   $clot->date_pod?->format('d/m/Y') ?? '—',
                    )
                );
            }
        }
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeWithAlertes($query)
    {
        return $query->where(function ($q) {
            $q->where('alerte_retard_mad', true)
              ->orWhere('alerte_facture_manquante', true)
              ->orWhere('alerte_transitaire_manquant', true)
              ->orWhere('alerte_pod_manquante', true)
              ->orWhere('alerte_livraison_depassee', true)
              ->orWhere('alerte_cout_reel_manquant', true)
              ->orWhere('alerte_mad_reelle_manquante', true)
              ->orWhere('alerte_validation_documents_manquante', true)
              ->orWhere('alerte_co_coc_manquant', true)
              ->orWhere('alerte_transitaire_infos_manquantes', true);
        });
    }

    public function scopeEnCours($query)
    {
        return $query->whereNotIn('statut', ['finalise']);
    }

    public function scopeFinalises($query)
    {
        return $query->where('statut', 'finalise');
    }

    // ── Static Helpers ─────────────────────────────────────────────

    public static function genererReference(): string
    {
        $year = now()->year;
        $prefix = "DOS-{$year}-";

        do {
            $sequence = static::withTrashed()->where('reference', 'like', "{$prefix}%")->count() + 1;
            $reference = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }
}