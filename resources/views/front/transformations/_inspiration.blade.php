{{-- « Inspiré de » : modèle d'origine d'une demande de transformation. Attend $demande et $lien (URL du modèle ou null). --}}
@if($inspiration = $demande->inspiration)
<section class="flex items-center gap-4 rounded-2xl border border-violet-200 bg-violet-50 p-4">
    @if($inspiration->photoUrl())<img src="{{ $inspiration->photoUrl() }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover">
    @else<span class="grid h-16 w-16 shrink-0 place-items-center rounded-xl bg-white text-violet-600"><i data-lucide="sparkles" class="h-6 w-6"></i></span>@endif
    <div class="min-w-0 flex-1 text-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Projet inspiré de</p>
        <p class="font-display text-base font-bold text-slate-900">{{ $inspiration->titre }}</p>
        <p class="text-slate-500">{{ $inspiration->prixFormate() }}{{ $inspiration->delai_jours ? ' · délai habituel '.$inspiration->delai_jours.' j' : '' }}</p>
    </div>
    @if($lien)<a href="{{ $lien }}" class="shrink-0 text-sm font-semibold text-violet-700 hover:underline">Voir le modèle</a>@endif
</section>
@endif
