{{-- Aperçu IA joint par le particulier : le vêtement tel qu'il l'imagine une fois transformé. Attend $demande. --}}
@if($demande->apercu_ia)
<section class="flex items-center gap-4 rounded-2xl border border-violet-200 bg-white p-4">
    <a href="{{ $demande->apercuIaUrl() }}" target="_blank" class="shrink-0"><img src="{{ $demande->apercuIaUrl() }}" alt="Aperçu généré par l’IA" class="h-24 w-24 rounded-xl object-cover"></a>
    <div class="text-sm">
        <p class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-violet-600"><i data-lucide="wand-sparkles" class="h-3.5 w-3.5"></i>Aperçu généré par l’IA</p>
        <p class="mt-1 text-slate-600">Image indicative du résultat souhaité, à partir de la photo du vêtement et de l’idée du projet.</p>
    </div>
</section>
@endif
