@extends('layouts.back')
@section('title', 'Avis utilisateurs')
@section('breadcrumb', 'Qualité / Modération des avis')
@section('content')
<div class="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-card title="Note moyenne" :value="$stats['moyenne'] ? number_format($stats['moyenne'], 1, ',', '').' / 5' : '—'" icon="star" accent="amber" />
    <x-card title="Avis publiés" :value="$stats['publies']" icon="message-square" accent="blue" />
    <x-card title="Avis masqués" :value="$stats['masques']" icon="eye-off" accent="violet" />
    <x-card title="Avec réponse" :value="$stats['reponses']" icon="reply" accent="green" />
</div>
<form method="GET" action="{{ route('admin.reviews.index') }}" class="mb-6 flex flex-wrap gap-3">
    <select name="statut" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold"><option value="">Tous les avis</option>@foreach(\App\Enums\StatutReview::cases() as $statut)<option value="{{ $statut->value }}" @selected(request('statut') === $statut->value)>{{ $statut->label() }}s</option>@endforeach</select>
    <select name="note" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold"><option value="">Toutes les notes</option>@foreach([1, 2, 3] as $n)<option value="{{ $n }}" @selected(request('note') == $n)>{{ $n }} étoile{{ $n > 1 ? 's' : '' }} ou moins</option>@endforeach</select>
</form>
<div class="space-y-4">
    @forelse($reviews as $review)
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm {{ $review->statut->value === 'masque' ? 'opacity-70' : '' }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-forest-100 font-bold text-forest-700">{{ strtoupper(mb_substr($review->particulier->name, 0, 2)) }}</span><div><h2 class="font-semibold">{{ $review->particulier->name }}</h2><p class="text-xs text-slate-500">sur {{ $review->reviewable?->nom }} · {{ $review->created_at->format('d/m/Y') }}</p></div></div>
            <div class="flex items-center gap-3"><x-etoiles :note="$review->note" /><x-badge :label="$review->statut->label()" :color="$review->statut->color()" /></div>
        </div>
        @if($review->commentaire)<p class="mt-3 text-sm leading-6 text-slate-600">“{{ $review->commentaire }}”</p>@endif
        @if($review->reponse)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm"><span class="font-semibold">Réponse :</span> {{ $review->reponse }}</p>@endif
        <div class="mt-4 flex gap-4 text-xs font-semibold">
            <form method="POST" action="{{ route('admin.reviews.basculer', $review) }}">@csrf<button class="{{ $review->statut->value === 'masque' ? 'text-forest-700' : 'text-amber-700' }}">{{ $review->statut->value === 'masque' ? 'Republier' : 'Masquer' }}</button></form>
            <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" data-confirmer="Supprimer définitivement cet avis ?">@csrf @method('DELETE')<button class="text-red-600">Supprimer</button></form>
        </div>
    </article>
    @empty
    <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500">Aucun avis.</p>
    @endforelse
</div>
<div class="mt-6">{{ $reviews->links() }}</div>
@endsection
