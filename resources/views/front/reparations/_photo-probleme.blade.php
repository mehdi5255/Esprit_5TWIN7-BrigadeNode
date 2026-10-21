{{-- Photo de l'abîmement envoyée par le particulier avec sa demande. Attend $demande. --}}
@if($demande->photo_probleme)
<section class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <a href="{{ $demande->photoProblemeUrl() }}" target="_blank" class="shrink-0"><img src="{{ $demande->photoProblemeUrl() }}" alt="Photo du problème" class="h-24 w-24 rounded-xl object-cover"></a>
    <div class="text-sm">
        <p class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-amber-700"><i data-lucide="camera" class="h-3.5 w-3.5"></i>Photo du problème</p>
        <p class="mt-1 text-slate-600">Prise par le particulier au moment de la demande. Cliquez pour l’agrandir.</p>
    </div>
</section>
@endif
