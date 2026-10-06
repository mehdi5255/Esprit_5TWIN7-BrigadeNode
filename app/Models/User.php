<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StatutCompte;
use App\Models\Ateliers\Atelier;
use App\Models\Dons\Association;
use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Review;
use App\Models\Vetements\Vetement;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Available roles, keyed by stored value.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'particulier' => 'Particulier',
        'atelier' => 'Atelier',
        'association' => 'Association',
        'admin' => 'Administrateur',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'statut',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'statut' => StatutCompte::class,
        ];
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function estBloque(): bool
    {
        return $this->statut === StatutCompte::BLOQUE;
    }

    public function estEnAttente(): bool
    {
        return $this->statut === StatutCompte::EN_ATTENTE;
    }

    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class);
    }

    public function demandesReparation(): HasMany
    {
        return $this->hasMany(DemandeReparation::class, 'particulier_id');
    }

    public function demandesTransformation(): HasMany
    {
        return $this->hasMany(DemandeTransformation::class, 'particulier_id');
    }

    public function demandesDon(): HasMany
    {
        return $this->hasMany(DemandeDon::class, 'particulier_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'particulier_id');
    }

    /** Atelier géré par un compte « atelier ». */
    public function atelier(): HasOne
    {
        return $this->hasOne(Atelier::class, 'responsable_id');
    }

    /** Association gérée par un compte « association ». */
    public function association(): HasOne
    {
        return $this->hasOne(Association::class, 'responsable_id');
    }

    /** Un compte a-t-il un historique qui interdit sa suppression ? */
    public function aUnHistorique(): bool
    {
        return match ($this->role) {
            'particulier' => $this->demandesReparation()->exists() || $this->demandesTransformation()->exists() || $this->demandesDon()->exists(),
            'atelier' => (bool) $this->atelier && ($this->atelier->demandesReparation()->exists() || $this->atelier->demandesTransformation()->exists()),
            'association' => (bool) $this->association && $this->association->demandesDon()->exists(),
            default => false,
        };
    }

    /** Page d'accueil selon le rôle, après connexion. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin' => route('admin.dashboard'),
            'atelier' => route('atelier.dashboard'),
            'association' => route('association.dashboard'),
            default => route('vetements.index'),
        };
    }
}
