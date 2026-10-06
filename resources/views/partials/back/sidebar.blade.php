@php
    $user = auth()->user();
    $compteur = fn ($relation) => $relation?->where('statut', 'en_attente')->count() ?: null;
    $depots = \App\Support\Depots::pour($user);

    // [icône, libellé, route, motif actif, compteur]
    $sections = match ($user->role) {
        'admin' => [
            'Pilotage' => [
                ['layout-dashboard', 'Tableau de bord', 'admin.dashboard', 'admin.dashboard', null],
                ['users', 'Utilisateurs', 'admin.users.index', 'admin.users.*', \App\Models\User::where('statut', 'en_attente')->count() ?: null],
                ['warehouse', 'Ateliers', 'admin.ateliers.index', 'admin.ateliers.*', null],
                ['heart-handshake', 'Associations', 'admin.associations.index', 'admin.associations.*', null],
                ['tags', 'Catégories', 'admin.categories.index', 'admin.categories.*', null],
            ],
            'Supervision' => [
                ['wrench', 'Réparations', 'admin.reparations.index', 'admin.reparations.*', null],
                ['scissors', 'Transformations', 'admin.transformations.index', 'admin.transformations.*', null],
                ['gift', 'Dons', 'admin.dons.index', 'admin.dons.*', null],
                ['package-check', 'Dépôts', 'admin.depots.index', 'admin.depots.*', $depots->nombreEnRetard() ?: null],
                ['star', 'Avis', 'admin.reviews.index', 'admin.reviews.*', null],
                ['file-bar-chart', 'Rapports', 'admin.rapports.index', 'admin.rapports.*', null],
            ],
        ],
        'atelier' => [
            'Mon atelier' => [
                ['layout-dashboard', 'Tableau de bord', 'atelier.dashboard', 'atelier.dashboard', null],
                ['store', 'Profil', 'atelier.profil.edit', 'atelier.profil.*', null],
                ['sparkles', 'Services', 'atelier.services.index', 'atelier.services.*', null],
            ],
            'Demandes' => [
                ['wrench', 'Réparations', 'atelier.reparations.index', 'atelier.reparations.*', $compteur($user->atelier?->demandesReparation())],
                ['scissors', 'Transformations', 'atelier.transformations.index', 'atelier.transformations.*', $compteur($user->atelier?->demandesTransformation())],
                ['package-check', 'Dépôts', 'atelier.depots.index', 'atelier.depots.*', $depots->nombreAttendus() ?: null],
                ['star', 'Avis reçus', 'atelier.avis.index', 'atelier.avis.*', null],
            ],
        ],
        'association' => [
            'Mon association' => [
                ['layout-dashboard', 'Tableau de bord', 'association.dashboard', 'association.dashboard', null],
                ['heart-handshake', 'Profil', 'association.profil.edit', 'association.profil.*', null],
                ['clipboard-list', 'Besoins', 'association.besoins.index', 'association.besoins.*', null],
            ],
            'Dons' => [
                ['gift', 'Dons reçus', 'association.dons.index', 'association.dons.*', $compteur($user->association?->demandesDon())],
                ['package-check', 'Dépôts', 'association.depots.index', 'association.depots.*', $depots->nombreAttendus() ?: null],
                ['star', 'Avis reçus', 'association.avis.index', 'association.avis.*', null],
            ],
        ],
    };
@endphp
<aside data-sidebar class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col overflow-y-auto bg-ink px-4 py-6 text-white transition-transform lg:translate-x-0">
    <a href="{{ $user->homeRoute() }}" class="mb-8 flex items-center gap-3 px-2">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-forest-500"><i data-lucide="recycle" class="h-5 w-5"></i></span>
        <span class="font-display text-xl font-extrabold">TextileCycle</span>
    </a>
    @foreach($sections as $titre => $liens)
    <p class="mb-3 {{ $loop->first ? '' : 'mt-8' }} px-3 text-[11px] font-bold uppercase tracking-widest text-slate-500">{{ $titre }}</p>
    <nav class="space-y-1 text-sm">
        @foreach($liens as [$icon, $label, $route, $pattern, $count])
        <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ request()->routeIs($pattern) ? 'bg-forest-500 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i>{{ $label }}@if($count)<span class="ml-auto rounded-full bg-amber-400/20 px-2 py-0.5 text-xs text-amber-300">{{ $count }}</span>@endif</a>
        @endforeach
    </nav>
    @endforeach
    <a href="{{ route('home') }}" class="mt-auto mb-3 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-300 hover:bg-white/5 hover:text-white"><i data-lucide="external-link" class="h-4 w-4"></i>Voir le site</a>
    <div class="rounded-2xl bg-white/5 p-3">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-forest-500 text-sm font-bold">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span>
            <a href="{{ route('compte.edit') }}" class="min-w-0 flex-1 hover:text-emerald-200" title="Mon compte"><p class="truncate text-sm font-semibold">{{ $user->name }}</p><p class="text-xs text-slate-400">{{ $user->roleLabel() }} · Mon compte</p></a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white" aria-label="Se déconnecter" title="Se déconnecter"><i data-lucide="log-out" class="h-4 w-4"></i></button></form>
        </div>
    </div>
</aside>
