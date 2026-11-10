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
    public const ALERTES_CUTOFF = '2026-08-17';

    /**
     * Alertes actives (demande client du 10/11/2026). Une seule source de vérité
     * pour les libellés, le filtre de la liste, l'ancre de l'étape concernée
     * et le mail quotidien.
     *
     *   slug    → valeur du filtre ?filterAlertes= sur la liste des dossiers
     *   etape   → ancre de l'étape sur la fiche dossier (#etape-…)
     */
    public const ALERTES = [
        'alerte_validation_documents_manquante' => [
            'slug'   => 'validation',
            'etape'  => 'mad',
            'label'  => 'Validation documents en attente',
            'detail' => 'Plus de 3 jours (délai par défaut) depuis la demande de validation aux commerciaux, « Date validation document » vide.',
        ],
        'alerte_co_coc_manquant' => [
            'slug'   => 'coc-coo',
            'etape'  => 'facturation',
            'label'  => 'COC / COO non reçu',
            'detail' => 'Facturé depuis 3 jours ou plus, COC et/ou COO pas encore reçu.',
        ],
        'alerte_transitaire_infos_manquantes' => [
            'slug'   => 'transitaire',
            'etape'  => 'transitaire',
            'label'  => 'Infos transitaire non reçues',
            'detail' => 'Infos reçues du client depuis 3 jours ou plus, « Date réception infos transitaire » vide.',
        ],
        'alerte_livraison_depassee' => [
            'slug'   => 'livraison',
            'etape'  => 'livraison',
            'label'  => 'Date de livraison réelle manquante',
            'detail' => 'Date de livraison prévue dépassée de 2 jours ou plus, « Date livraison réelle » vide.',
        ],
        'alerte_pod_manquante' => [
            'slug'   => 'pod',
            'etape'  => 'cloture',
            'label'  => 'Date POD manquante',
            'detail' => 'Livré depuis 4 jours ou plus, « Date POD » vide.',
        ],
    ];

    /** Délais (en jours) des alertes, tels que demandés par le client. */
    public const DELAI_COC_COO          = 3;
    public const DELAI_VALIDATION_DEFAUT = 3;
    public const DELAI_INFOS_TRANSITAIRE = 3;
    public const DELAI_LIVRAISON_REELLE  = 2;
    public const DELAI_POD               = 4;

    /**
     * Anciennes alertes que le client n'a pas redemandées : colonnes conservées
     * (historique, rollback facile) mais toujours remises à false.
     */
    public const ALERTES_RETIREES = [
        'alerte_retard_mad',
        'alerte_facture_manquante',
        'alerte_transitaire_manquant',
        'alerte_cout_reel_manquant',
        'alerte_mad_reelle_manquante',
    ];

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

    /**
     * Alertes levées sur ce dossier, dans l'ordre des étapes.
     *
     * @return array<string, array{slug: string, etape: string, label: string, detail: string}>
     */
    public function getAlertesActivesAttribute(): array
    {
        return array_filter(self::ALERTES, fn ($_, string $col) => (bool) $this->{$col}, ARRAY_FILTER_USE_BOTH);
    }

    public function getAlertLabelsAttribute(): array
    {
        return array_values(array_column($this->alertes_actives, 'label'));
    }

    public function getHasAlerteAttribute(): bool
    {
        return $this->alertes_actives !== [];
    }

    /** Lien vers la fiche, positionné sur l'étape de la première alerte. */
    public function getUrlAlerteAttribute(): string
    {
        $premiere = array_values($this->alertes_actives)[0] ?? null;

        return route('dossiers.show', $this->id) . ($premiere ? '#etape-' . $premiere['etape'] : '');
    }

    /**
     * N° de facture à afficher : celui saisi à l'étape 2 (Facturation), sinon
     * celui de l'en-tête dossier (seul renseigné sur les affaires importées).
     */
    public function getNumeroFactureAfficheAttribute(): ?string
    {
        return $this->etapeFacturation?->numero_facture ?: ($this->numero_facture ?: null);
    }

    public function getIncotermLabelAttribute(): string
    {
        return match($this->incoterm) {
            'FCA_USINE'       => 'FCA Usine',
            'FCA_TRANSITAIRE' => 'FCA Transitaire',
            'CPT'             => 'CPT',
            'CFR'             => 'CFR',
            'EXW'             => 'EXW',
            'FOB'             => 'FOB',
            'CIF'             => 'CIF',
            'AUTRES'          => 'Autres',
            default           => $this->incoterm,
        };
    }

    // ── Business Methods ───────────────────────────────────────────

    public function refreshAlertes(): void
    {
        foreach (self::ALERTES_RETIREES as $col) {
            $this->{$col} = false;
        }

        $levees = $this->calculerAlertes();
        foreach (array_keys(self::ALERTES) as $col) {
            $this->{$col} = $levees[$col] ?? false;
        }

        $this->saveQuietly();
    }

    /**
     * @return array<string, bool> colonne d'alerte → levée ou non
     */
    private function calculerAlertes(): array
    {
        // Pas de bruit sur l'historique repris : les alertes ne s'appliquent
        // qu'aux dossiers créés à partir du cutoff.
        if (! $this->created_at || $this->created_at->lt(self::ALERTES_CUTOFF)) {
            return [];
        }

        // Un dossier finalisé n'a plus d'action en attente : jamais d'alerte.
        if ($this->statut === 'finalise' || $this->etapeCloture?->complete) {
            return [];
        }

        $today = today();
        // Vrai à partir du jour « date + N jours » (ex. facturé le 10, N = 3 → alerte le 13).
        $echu = fn ($date, int $jours) => $date && $today->gte($date->copy()->startOfDay()->addDays($jours));

        $mad   = $this->etapeMadFournisseur;
        $fact  = $this->etapeFacturation;
        $trans = $this->etapeTransitaire;
        $liv   = $this->etapeLivraison;
        $clot  = $this->etapeCloture;

        return [
            // Étape 1 — Validation des documents : plus de N jours (3 par défaut)
            // entre la demande aux commerciaux et la validation.
            'alerte_validation_documents_manquante' => $mad
                && ! $mad->date_validation_document
                && $echu($mad->date_demande_validation, ($mad->delai_validation_jours ?: self::DELAI_VALIDATION_DEFAUT) + 1),

            // Étape 2 — Facturation : 3 jours après la date de facturation,
            // COC et COO pas tous les deux reçus.
            'alerte_co_coc_manquant' => $fact
                && ! ($fact->co_recu && $fact->coc_recu)
                && $echu($fact->date_facturation, self::DELAI_COC_COO),

            // Étape 3 — Coordination transitaire : « Date réception infos
            // transitaire » vide 3 jours après les infos reçues du client.
            'alerte_transitaire_infos_manquantes' => $trans
                && ! $trans->date_reception_infos_transitaire
                && $echu($trans->date_infos_client, self::DELAI_INFOS_TRANSITAIRE),

            // Étape 4a — Livraison client : « Date livraison réelle » vide
            // 2 jours après la date prévue (sauf livraison non applicable).
            'alerte_livraison_depassee' => $liv
                && $liv->applicable
                && ! $liv->date_livraison_reelle
                && $echu($liv->date_livraison_prevue, self::DELAI_LIVRAISON_REELLE),

            // Étape 4b — Clôture : « Date POD » vide 4 jours après la livraison réelle.
            'alerte_pod_manquante' => $liv
                && ! $clot?->date_pod
                && $echu($liv->date_livraison_reelle, self::DELAI_POD),
        ];
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
            foreach (array_keys(self::ALERTES) as $col) {
                $q->orWhere($col, true);
            }
        });
    }

    /** Filtre sur une alerte précise via son slug (cf. ALERTES). */
    public function scopeWithAlerte($query, string $slug)
    {
        $col = self::colonneAlerte($slug);

        return $col ? $query->where($col, true) : $query->withAlertes();
    }

    public static function colonneAlerte(string $slug): ?string
    {
        foreach (self::ALERTES as $col => $def) {
            if ($def['slug'] === $slug) {
                return $col;
            }
        }

        return null;
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