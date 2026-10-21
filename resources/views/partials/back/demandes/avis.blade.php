{{-- Avis laissé sur la demande, vu côté gestion. Attend $demande. --}}
@if($demande->review)
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3"><h2 class="font-display text-lg font-bold">Avis du particulier</h2><x-etoiles :note="$demande->review->note" /></div>
    @if($demande->review->commentaire)<p class="mt-3 text-sm text-slate-600">“{{ $demande->review->commentaire }}”</p>@endif
    @if($demande->review->reponse)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm"><span class="font-semibold">Réponse :</span> {{ $demande->review->reponse }}</p>@endif
</section>
@endif
