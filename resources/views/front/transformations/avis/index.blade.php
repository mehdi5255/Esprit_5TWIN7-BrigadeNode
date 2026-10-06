@extends('layouts.front')
@section('title', 'Mes avis')
@section('content')
@php $segments = ['reparation' => 'reparations', 'transformation' => 'transformations', 'don' => 'dons']; @endphp
<section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
    <div class="mb-8"><p class="text-sm font-bold text-forest-600">Votre expérience compte</p><h1 class="mt-2 font-display text-4xl font-bold">Mes avis</h1><p class="mt-2 text-slate-500">Les avis que vous avez laissés aux ateliers et associations.</p></div>
    <div class="space-y-4">
        @forelse($reviews as $review)
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-display text-lg font-bold">{{ $review->reviewable?->nom ?? 'Structure supprimée' }}</p>
                    <p class="text-sm text-slate-500">{{ $review->demande?->vetement?->nom ?? 'Demande supprimée' }} · {{ $review->created_at->translatedFormat('d F Y') }}</p>
                </div>
                <div class="flex items-center gap-3"><x-etoiles :note="$review->note" />@if($review->statut->value === 'masque')<x-badge label="Masqué" color="gray" />@endif</div>
            </div>
            @if($review->commentaire)<p class="mt-3 text-sm leading-6 text-slate-600">“{{ $review->commentaire }}”</p>@endif
            @if($review->reponse)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm"><span class="font-semibold">Réponse :</span> {{ $review->reponse }}</p>@endif
            <div class="mt-4 flex gap-4 text-sm font-semibold">
                @if($review->demande)<a href="{{ route($segments[$review->demande_type].'.show', $review->demande_id) }}" class="text-forest-700 hover:underline">Modifier depuis la demande</a>@endif
                <form method="POST" action="{{ route('avis.destroy', $review) }}" data-confirmer="Supprimer cet avis ?">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Supprimer</button></form>
            </div>
        </article>
        @empty
        <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500">Vous n'avez pas encore laissé d'avis. Vous pourrez noter un atelier ou une association à la fin de chaque demande.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $reviews->links() }}</div>
</section>
@endsection
