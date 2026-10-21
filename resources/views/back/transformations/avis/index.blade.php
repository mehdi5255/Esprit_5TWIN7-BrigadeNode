@extends('layouts.back')
@section('title', 'Avis reçus')
@section('breadcrumb', $structure->nom . ' / Avis')
@section('content')
<div class="mb-6 grid gap-5 sm:grid-cols-3">
    <x-card title="Note moyenne" :value="$structure->noteMoyenne() ? number_format($structure->noteMoyenne(), 1, ',', '').' / 5' : '—'" icon="star" accent="amber" />
    <x-card title="Avis publiés" :value="$structure->nombreAvis()" icon="message-square" accent="blue" />
    <x-card title="Avis sans réponse" :value="$structure->reviews()->publies()->whereNull('reponse')->count()" icon="reply" accent="green" />
</div>
<div class="space-y-4">
    @forelse($reviews as $review)
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-forest-100 font-bold text-forest-700">{{ strtoupper(mb_substr($review->particulier->name, 0, 2)) }}</span><div><h2 class="font-semibold">{{ $review->particulier->name }}</h2><p class="text-xs text-slate-500">{{ $review->demande?->reference() }} · {{ $review->created_at->translatedFormat('d F Y') }}</p></div></div>
            <x-etoiles :note="$review->note" />
        </div>
        @if($review->commentaire)<p class="mt-3 text-sm leading-6 text-slate-600">“{{ $review->commentaire }}”</p>@endif
        @if($review->reponse)
            <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm"><p class="text-xs font-semibold text-forest-700">Votre réponse · {{ $review->date_reponse?->format('d/m/Y') }}</p><p class="mt-1 text-slate-600">{{ $review->reponse }}</p></div>
        @else
            {{-- Un sac d'erreurs par avis : l'erreur et la saisie reviennent sous le bon formulaire. --}}
            @php $sac = 'reponse_'.$review->id; $enErreur = $errors->getBag($sac)->any(); @endphp
            <details class="mt-4" @if($enErreur) open @endif>
                <summary class="cursor-pointer text-xs font-semibold text-forest-700">Répondre</summary>
                <form method="POST" action="{{ route($espace.'.avis.repondre', $review) }}" class="mt-3 space-y-3">@csrf
                    <textarea name="reponse" rows="3" required class="w-full rounded-xl border {{ $enErreur ? 'border-red-400' : 'border-slate-200' }} px-4 py-3 text-sm outline-none focus:border-forest-500 focus:ring-4 focus:ring-forest-100" placeholder="Remerciez ou apportez une précision…">{{ $enErreur ? old('reponse') : '' }}</textarea>
                    @error('reponse', $sac)<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                    <button class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white hover:bg-forest-700">Publier la réponse</button>
                </form>
            </details>
        @endif
    </article>
    @empty
    <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500">Aucun avis reçu pour le moment.</p>
    @endforelse
</div>
<div class="mt-6">{{ $reviews->links() }}</div>
@endsection
