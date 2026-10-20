<?php

namespace App\Models;

use App\Enums\StatutCompte;
use App\Models\Ateliers\Atelier;
use App\Models\Dons\Association;
use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\Transformations\Review;
use App\Models\Vetements\Vetement;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/** Utilisateur de la plateforme. L'adresse e-mail doit être vérifiée avant d'accéder aux espaces (MustVerifyEmail). */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Available roles, keyed by stored value.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'particulier' => 'Particulier',
        'atelier' => 'Atelier',
        'association' => 'Association',
        'moderateur' => 'Modérateur',
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
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /** Le type de compte (colonne « role ») reste synchronisé avec le rôle Spatie qui porte les permissions. */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
                $user->syncRoles([Role::findOrCreate($user->role, 'web')]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'statut' => StatutCompte::class,
        ];
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
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
            'admin', 'moderateur' => route('admin.dashboard'),
            'atelier' => route('atelier.dashboard'),
            'association' => route('association.dashboard'),
            default => route('vetements.index'),
        };
    }
}
