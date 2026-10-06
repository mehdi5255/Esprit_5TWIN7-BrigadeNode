<?php

namespace App\Providers;

use App\Models\Ateliers\Atelier;
use App\Models\Dons\Association;
use App\Models\Dons\DemandeDon;
use App\Models\Reparations\DemandeReparation;
use App\Models\Transformations\DemandeTransformation;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Noms courts stockés dans les colonnes polymorphes (reviews, notifications).
        Relation::enforceMorphMap([
            'user' => User::class,
            'atelier' => Atelier::class,
            'association' => Association::class,
            'reparation' => DemandeReparation::class,
            'transformation' => DemandeTransformation::class,
            'don' => DemandeDon::class,
        ]);

        // Limites anti force brute, comptées séparément pour chaque formulaire.
        RateLimiter::for('connexion', fn (Request $request) => Limit::perMinute(6)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('mot-de-passe', fn (Request $request) => Limit::perMinute(3)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('inscription', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // E-mail de réinitialisation du mot de passe, en français.
        ResetPassword::toMailUsing(fn (User $user, string $token) => (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe TextileCycle')
            ->greeting("Bonjour {$user->name},")
            ->line('Vous avez demandé à réinitialiser le mot de passe de votre compte TextileCycle.')
            ->action('Choisir un nouveau mot de passe', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('Ce lien expire dans '.config('auth.passwords.users.expire').' minutes.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail.')
            ->salutation('L’équipe TextileCycle'));
    }
}
