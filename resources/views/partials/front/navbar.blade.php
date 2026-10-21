@php
    $user = auth()->user();
    $particulier = $user?->hasRole('particulier');
    // [URL, lien actif ?, libellé]. Inspirations fait partie de Transformations ; besoins et événements forment « Solidarité ».
    $ateliersReparation = request()->routeIs('ateliers.index') && request('service') === 'reparation';
    $solidarite = request()->routeIs('besoins.*', 'evenements.*');
    $liens = $particulier
        ? [
            [route('vetements.index'), request()->routeIs('vetements.*'), 'Mes vêtements'],
            [route('reparations.index'), request()->routeIs('reparations.*'), 'Réparations'],
            [route('transformations.index'), request()->routeIs('transformations.*', 'inspirations.*'), 'Transformations'],
            [route('dons.index'), request()->routeIs('dons.*'), 'Dons'],
            [route('besoins.index'), $solidarite, 'Solidarité'],
            [route('ateliers.index'), request()->routeIs('ateliers.*'), 'Ateliers'],
            [route('associations.index'), request()->routeIs('associations.*'), 'Associations'],
        ]
        : [
            [route('home'), request()->routeIs('home'), 'Accueil'],
            [route('ateliers.index', ['service' => 'reparation']), $ateliersReparation, 'Réparations'],
            [route('inspirations.index'), request()->routeIs('inspirations.*'), 'Transformations'],
            [route('besoins.index'), $solidarite, 'Solidarité'],
            [route('ateliers.index'), request()->routeIs('ateliers.*') && ! $ateliersReparation, 'Ateliers'],
            [route('associations.index'), request()->routeIs('associations.*'), 'Associations'],
        ];
@endphp
<header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/90 backdrop-blur">
    <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Navigation principale">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-forest-700 text-white"><i data-lucide="recycle" class="h-5 w-5"></i></span>
            <span class="font-display text-xl font-extrabold tracking-tight text-forest-900">Textile<span class="text-forest-500">Cycle</span></span>
        </a>
        <div class="hidden items-center gap-6 text-sm font-semibold text-slate-600 lg:flex">
            @foreach($liens as [$url, $actif, $label])
            <a href="{{ $url }}" class="{{ $actif ? 'text-forest-700' : 'transition hover:text-forest-700' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="flex items-center gap-3">
            @auth
                @php $nonLues = $user->unreadNotifications()->count(); @endphp
                <a href="{{ route('notifications.index') }}" class="relative hidden rounded-xl p-2.5 text-slate-500 hover:bg-slate-100 sm:block" aria-label="Notifications{{ $nonLues ? " ($nonLues non lues)" : '' }}">
                    <i data-lucide="bell" class="h-5 w-5"></i>
                    @if($nonLues)<span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-clay px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ $nonLues }}</span>@endif
                </a>
                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-slate-200 bg-white p-1.5 pr-3 text-sm font-semibold">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-forest-100 text-forest-700">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span>
                        <span class="hidden sm:inline">{{ $user->name }}</span>
                        <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400"></i>
                    </summary>
                    <div class="absolute right-0 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 text-sm shadow-xl">
                        <p class="px-3 py-2 text-xs text-slate-400">{{ $user->roleLabel() }}</p>
                        @if($particulier)
                            @foreach([['shirt', 'Mes vêtements', 'vetements.index'], ['wrench', 'Mes réparations', 'reparations.index'], ['scissors', 'Mes transformations', 'transformations.index'], ['gift', 'Mes dons', 'dons.index'], ['star', 'Mes avis', 'avis.index'], ['clipboard-list', 'Besoins des associations', 'besoins.index']] as [$icone, $libelle, $route])
                            <a href="{{ route($route) }}" class="flex items-center gap-2 rounded-xl px-3 py-2 hover:bg-slate-50"><i data-lucide="{{ $icone }}" class="h-4 w-4"></i>{{ $libelle }}</a>
                            @endforeach
                        @else
                            <a href="{{ $user->homeRoute() }}" class="flex items-center gap-2 rounded-xl px-3 py-2 hover:bg-slate-50"><i data-lucide="layout-dashboard" class="h-4 w-4"></i>Espace de gestion</a>
                        @endif
                        <a href="{{ route('notifications.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 hover:bg-slate-50"><i data-lucide="bell" class="h-4 w-4"></i>Notifications</a>
                        <a href="{{ route('compte.edit') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 hover:bg-slate-50"><i data-lucide="user-cog" class="h-4 w-4"></i>Mon compte</a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">@csrf<button class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-red-600 hover:bg-red-50"><i data-lucide="log-out" class="h-4 w-4"></i>Se déconnecter</button></form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="hidden text-sm font-semibold text-slate-600 hover:text-forest-700 sm:block">Se connecter</a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="user-plus" class="h-4 w-4"></i>S'inscrire</a>
            @endauth
        </div>
    </nav>
</header>
