<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'poids' => 'decimal:2',
        'cout_transitaire' => 'decimal:2',
        'alerte_retard_mad' => 'boolean',
        'alerte_facture_manquante' => 'boolean',
        'alerte_transitaire_manquant' => 'boolean',
        'alerte_pod_manquante' => 'boolean',
        'alerte_livraison_depassee' => 'boolean',
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

    public function getStatutColorAttribute(): string
    {
        return match($this->statut) {
            'en_attente'      => 'gray',
            'mad_fournisseur' => 'blue',
            'facture'         => 'purple',
            'transitaire_ok'  => 'yellow',
            'enleve'          => 'orange',
            'livre'           => 'teal',
            'finalise'        => 'green',
            default           => 'gray',
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

        // Alerte retard MAD
        $mad = $this->etapeMadFournisseur;
        $this->alerte_retard_mad = $mad
            && $mad->date_mad_prevue
            && ! $mad->date_mad_reelle
            && $mad->date_mad_prevue < $today;

        // Alerte facture manquante (MAD faite mais pas encore facturé)
        $fact = $this->etapeFacturation;
        $this->alerte_facture_manquante = $mad
            && $mad->complete
            && $fact
            && ! $fact->facture_emise;

        // Alerte transitaire manquant (facturé mais transitaire pas communiqué)
        $trans = $this->etapeTransitaire;
        $this->alerte_transitaire_manquant = $fact
            && $fact->complete
            && $trans
            && ! $trans->transitaire_communique;

        // Alerte livraison dépassée
        $liv = $this->etapeLivraison;
        $this->alerte_livraison_depassee = $liv
            && $liv->date_livraison_prevue
            && ! $liv->date_livraison_reelle
            && $liv->date_livraison_prevue < $today;

        // Alerte POD manquante (enlevé mais pas de POD)
        $clot = $this->etapeCloture;
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
        } elseif ($this->etapeMadFournisseur?->complete) {
            $this->statut = 'mad_fournisseur';
        } else {
            $this->statut = 'en_attente';
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
        $year = now()->year;
        $last = static::withTrashed()
            ->where('reference', 'like', "DOS-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? (int) substr($last->reference, strrpos($last->reference, '-') + 1) + 1
            : 1;

        return sprintf('DOS-%d-%04d', $year, $seq);
    }
}
