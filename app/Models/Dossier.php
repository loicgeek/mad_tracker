<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Dossier extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'reference', 'user_id', 'client_id', 'fournisseur_id',
        'numero_facture', 'reference_affaire', 'pays_destination',
        'incoterm', 'incoterm_lieu', 'categorie',
        'transitaire_nom', 'transitaire_contact',
        'poids', 'cout_transitaire',
        'statut',
        'alerte_retard_mad', 'alerte_facture_manquante',
        'alerte_transitaire_manquant', 'alerte_pod_manquante',
        'alerte_livraison_depassee',
    ];

    protected $casts = [
        'poids'                        => 'decimal:2',
        'cout_transitaire'             => 'decimal:2',
        'alerte_retard_mad'            => 'boolean',
        'alerte_facture_manquante'     => 'boolean',
        'alerte_transitaire_manquant'  => 'boolean',
        'alerte_pod_manquante'         => 'boolean',
        'alerte_livraison_depassee'    => 'boolean',
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

    public function getHasAlerteAttribute(): bool
    {
        return $this->alerte_retard_mad
            || $this->alerte_facture_manquante
            || $this->alerte_transitaire_manquant
            || $this->alerte_pod_manquante
            || $this->alerte_livraison_depassee;
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

        $this->alerte_transitaire_manquant = $fact
            && $fact->complete
            && $trans
            && ! $trans->transitaire_communique;

        $this->alerte_livraison_depassee = $liv
            && $liv->date_livraison_prevue
            && ! $liv->date_livraison_reelle
            && $liv->date_livraison_prevue < $today;

        $this->alerte_pod_manquante = $trans
            && $trans->complete
            && $clot
            && ! $clot->pod_recue;

        $this->saveQuietly();
    }

    public function recalculerStatut(): void
    {
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
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeWithAlertes($query)
    {
        return $query->where(function ($q) {
            $q->where('alerte_retard_mad', true)
              ->orWhere('alerte_facture_manquante', true)
              ->orWhere('alerte_transitaire_manquant', true)
              ->orWhere('alerte_pod_manquante', true)
              ->orWhere('alerte_livraison_depassee', true);
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
    return DB::transaction(function () {
        $year = now()->year;
        $prefix = "DOS-{$year}-";

        $maxSeq = DB::table((new static())->getTable())
            ->where('reference', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->max(DB::raw("CAST(SUBSTRING_INDEX(reference, '-', -1) AS UNSIGNED)"));

        $seq = $maxSeq ? $maxSeq + 1 : 1;

        return sprintf('DOS-%d-%04d', $year, $seq);
    });
}
}