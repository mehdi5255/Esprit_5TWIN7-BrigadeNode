{{-- Liste publique d'avis. Attend $reviews (paginator) et $structure. --}}
<div class="space-y-4">
    @forelse($reviews as $review)
    <article class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-full bg-forest-100 text-xs font-bold text-forest-700">{{ strtoupper(mb_substr($review->particulier->name, 0, 2)) }}</span><div><p class="text-sm font-semibold">{{ $review->particulier->name }}</p><p class="text-xs text-slate-400">{{ $review->created_at->translatedFormat('d F Y') }}</p></div></div>
            <x-etoiles :note="$review->note" />
        </div>
        @if($review->commentaire)<p class="mt-3 text-sm leading-6 text-slate-600">{{ $review->commentaire }}</p>@endif
        @if($review->reponse)<div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm"><p class="text-xs font-semibold text-forest-700">Réponse de {{ $structure->nom }}</p><p class="mt-1 text-slate-600">{{ $review->reponse }}</p></div>@endif
    </article>
    @empty
    <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Aucun avis pour le moment.</p>
    @endforelse
</div>
<div class="mt-6">{{ $reviews->links() }}</div>
