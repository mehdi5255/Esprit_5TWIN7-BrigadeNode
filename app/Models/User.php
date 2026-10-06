<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StatutCompte;
use App\Support\Modules;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Route;

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

    /** Relations vers les structures dont le module est présent, pour with() / load(). */
    public static function relationsStructure(): array
    {
        return array_values(Modules::filtrer(['ateliers' => 'atelier', 'associations' => 'association']));
    }

    /** Atelier ou association géré par ce compte (null si aucun, ou si le module n'est pas présent). */
    public function structure(): ?Model
    {
        return match (true) {
            $this->hasRole('atelier') && Modules::actif('ateliers') => $this->atelier,
            $this->hasRole('association') && Modules::actif('associations') => $this->association,
            default => null,
        };
    }

    /** Un compte a-t-il un historique qui interdit sa suppression ? */
    public function aUnHistorique(): bool
    {
        $existe = fn (string $module, \Closure $requete) => Modules::actif($module) && $requete();
        $structure = $this->structure();

        return match ($this->role) {
            'particulier' => $existe('reparations', fn () => $this->demandesReparation()->exists())
                || $existe('transformations', fn () => $this->demandesTransformation()->exists())
                || $existe('dons', fn () => $this->demandesDon()->exists()),
            'atelier' => (bool) $structure && ($existe('reparations', fn () => $structure->demandesReparation()->exists())
                || $existe('transformations', fn () => $structure->demandesTransformation()->exists())),
            'association' => (bool) $structure && $existe('dons', fn () => $structure->demandesDon()->exists()),
            default => false,
        };
    }

    /** Page d'accueil selon le rôle, après connexion (accueil du site si le module n'est pas encore présent). */
    public function homeRoute(): string
    {
        $route = match ($this->role) {
            'admin' => 'admin.dashboard',
            'atelier' => 'atelier.dashboard',
            'association' => 'association.dashboard',
            default => 'vetements.index',
        };

        return route(Route::has($route) ? $route : 'home');
    }
}
