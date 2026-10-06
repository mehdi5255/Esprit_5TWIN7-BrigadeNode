<footer class="mt-20 bg-forest-900 text-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
        <div>
            <div class="mb-4 flex items-center gap-3 font-display text-xl font-bold"><i data-lucide="recycle" class="h-6 w-6 text-emerald-300"></i>TextileCycle</div>
            <p class="max-w-sm text-sm leading-6 text-emerald-100/70">Réparer, transformer et transmettre pour prolonger la vie de chaque vêtement.</p>
        </div>
        <div><p class="mb-4 font-semibold">Explorer</p><div class="space-y-3 text-sm text-emerald-100/70">@foreach([['ateliers.index', 'Trouver un atelier'], ['besoins.index', 'Besoins des associations'], ['associations.index', 'Nos engagements']] as [$route, $libelle])@if(Route::has($route))<a href="{{ route($route) }}" class="block hover:text-white">{{ $libelle }}</a>@endif @endforeach</div></div>
        <div><p class="mb-4 font-semibold">Besoin d'aide ?</p><div class="space-y-3 text-sm text-emerald-100/70"><a href="#" class="block hover:text-white">Centre d'aide</a><a href="#" class="block hover:text-white">Nous contacter</a><a href="#" class="block hover:text-white">Confidentialité</a></div></div>
    </div>
    <div class="border-t border-white/10 px-4 py-5 text-center text-xs text-emerald-100/50">© {{ now()->year }} TextileCycle. Ensemble, faisons durer la mode.</div>
</footer>
