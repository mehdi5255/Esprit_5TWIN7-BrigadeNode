{{-- Onglets de l'espace Transformations (particulier) : ses projets et la galerie d'inspirations. --}}
@if(auth()->user()?->hasRole('particulier'))
<nav class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 text-sm font-semibold" aria-label="Transformations">
    @foreach([['transformations.index', 'transformations.*', 'scissors', 'Mes projets'], ['inspirations.index', 'inspirations.*', 'lightbulb', 'Inspirations']] as [$route, $motif, $icone, $libelle])
    <a href="{{ route($route) }}" class="-mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 {{ request()->routeIs($motif) ? 'border-violet-500 text-violet-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}"><i data-lucide="{{ $icone }}" class="h-4 w-4"></i>{{ $libelle }}</a>
    @endforeach
</nav>
@endif
