{{-- Authentification à deux facteurs (Laravel Fortify) : activation, QR code, confirmation, codes de récupération, désactivation. Attend $user. --}}
@php
    $active = $user->hasEnabledTwoFactorAuthentication();
    $enCours = $user->two_factor_secret && ! $active;
    $erreurMotDePasse = $errors->deuxFacteurs->first('mot_de_passe_2fa');
@endphp
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" id="deux-facteurs">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-display text-lg font-bold">Double authentification (2FA)</h2>
            <p class="mt-1 text-sm text-slate-500">À la connexion, en plus du mot de passe, un code à 6 chiffres généré par une application sur votre téléphone (Google Authenticator, Microsoft Authenticator…).</p>
        </div>
        @if($active)<x-badge label="Activée" color="green" />@elseif($enCours)<x-badge label="À confirmer" color="amber" />@else<x-badge label="Désactivée" color="gray" />@endif
    </div>
    @if($erreurMotDePasse)<p class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $erreurMotDePasse }}</p>@endif

    @if(! $user->two_factor_secret)
        {{-- Étape 1 : activer --}}
        <form method="POST" action="{{ route('compte.2fa.activer') }}" class="mt-5 flex flex-wrap items-end gap-3">@csrf
            <label class="block w-full max-w-xs"><span class="mb-2 block text-sm font-semibold text-slate-700">Confirmez avec votre mot de passe</span>
                <input type="password" name="mot_de_passe_2fa" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-forest-500 focus:ring-4 focus:ring-forest-100"></label>
            <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="shield-plus" class="h-4 w-4"></i>Activer la double authentification</button>
        </form>
    @elseif($enCours)
        {{-- Étape 2 : scanner le QR code, puis confirmer avec un premier code --}}
        <div class="mt-5 grid gap-6 sm:grid-cols-[auto,1fr] sm:items-start">
            <div class="w-fit rounded-2xl bg-white p-3 ring-1 ring-slate-200" data-qr-code>{!! $user->twoFactorQrCodeSvg() !!}</div>
            <div class="space-y-4 text-sm">
                <ol class="list-decimal space-y-1 pl-5 text-slate-600">
                    <li>Ouvrez votre application d’authentification et scannez ce QR code.</li>
                    <li>Ou saisissez la clé : <code class="select-all rounded bg-slate-100 px-2 py-1 font-mono text-slate-800">{{ decrypt($user->two_factor_secret) }}</code></li>
                    <li>Entrez le code à 6 chiffres affiché pour confirmer.</li>
                </ol>
                <form method="POST" action="{{ route('compte.2fa.confirmer') }}" class="flex flex-wrap items-end gap-3">@csrf
                    <label class="block w-40"><span class="mb-2 block text-sm font-semibold text-slate-700">Code</span>
                        <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required placeholder="123456" class="w-full rounded-xl border {{ $errors->confirmTwoFactorAuthentication->has('code') || $errors->has('code') ? 'border-red-400' : 'border-slate-200' }} bg-white px-4 py-3 text-sm tracking-widest outline-none focus:border-forest-500 focus:ring-4 focus:ring-forest-100"></label>
                    <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>Confirmer</button>
                </form>
                @if($message = $errors->confirmTwoFactorAuthentication->first('code') ?: $errors->first('code'))<p class="text-xs text-red-600">{{ $message }}</p>@endif
                <form method="POST" action="{{ route('compte.2fa.desactiver') }}" class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">@csrf @method('DELETE')
                    <input type="password" name="mot_de_passe_2fa" required placeholder="Mot de passe" autocomplete="current-password" class="w-48 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-forest-500">
                    <button class="text-sm font-semibold text-slate-500 hover:text-red-600">Annuler l’activation</button>
                </form>
            </div>
        </div>
    @else
        {{-- Active : codes de récupération et désactivation --}}
        @if(session('afficher_codes_2fa'))
        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm font-semibold text-amber-900">Vos codes de récupération</p>
            <p class="mt-1 text-xs text-amber-800">Si vous perdez votre téléphone, chacun de ces codes permet une connexion. Notez-les en lieu sûr : ils ne seront plus affichés.</p>
            <ul class="mt-3 grid gap-1 font-mono text-sm text-slate-800 sm:grid-cols-2" data-codes-recuperation>
                @foreach($user->recoveryCodes() as $code)<li class="rounded bg-white px-2 py-1">{{ $code }}</li>@endforeach
            </ul>
        </div>
        @endif
        <p class="mt-4 text-sm text-slate-600">Activée le {{ $user->two_factor_confirmed_at->translatedFormat('d F Y') }} · {{ count($user->recoveryCodes()) }} codes de récupération disponibles.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <form method="POST" action="{{ route('compte.2fa.codes') }}" class="space-y-2 rounded-xl bg-slate-50 p-4">@csrf
                <p class="text-sm font-semibold">Nouveaux codes de récupération</p>
                <input type="password" name="mot_de_passe_2fa" required placeholder="Mot de passe" autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-forest-500">
                <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"><i data-lucide="refresh-cw" class="h-4 w-4"></i>Régénérer</button>
            </form>
            <form method="POST" action="{{ route('compte.2fa.desactiver') }}" class="space-y-2 rounded-xl bg-red-50/60 p-4" data-confirmer="Désactiver la double authentification ?">@csrf @method('DELETE')
                <p class="text-sm font-semibold text-red-800">Désactiver la 2FA</p>
                <input type="password" name="mot_de_passe_2fa" required placeholder="Mot de passe" autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-red-400">
                <button class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"><i data-lucide="shield-off" class="h-4 w-4"></i>Désactiver</button>
            </form>
        </div>
    @endif
</section>
