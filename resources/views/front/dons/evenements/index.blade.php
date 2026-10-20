@extends('layouts.front')
@section('title', 'Solidarité — Collectes et distributions')
@section('meta', 'Les prochains rendez-vous solidaires des associations partenaires.')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @include('front.dons._solidarite')
    <form method="GET" action="{{ route('evenements.index') }}" class="mb-8 flex flex-wrap gap-3">
        <select name="type" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold" aria-label="Type">
            <option value="">Tous les événements</option>
            @foreach(\App\Enums\TypeEvenement::cases() as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}s</option>@endforeach
        </select>
        <select name="association" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold" aria-label="Association">
            <option value="">Toutes les associations</option>
            @foreach($associations as $association)<option value="{{ $association->id }}" @selected(request()->integer('association') === $association->id)>{{ $association->nom }}</option>@endforeach
        </select>
    </form>
    @if($points->isNotEmpty())
    @include('partials.carte.leaflet')
    <div class="mb-8">
        <div data-carte-evenements data-points='@json($points)' class="relative z-0 h-80 w-full overflow-hidden rounded-3xl ring-1 ring-slate-200" role="img" aria-label="Carte des événements"></div>
        <p class="mt-2 text-xs text-slate-500">{{ $points->count() }} rendez-vous sur la carte. Cliquez sur un repère pour voir le détail.</p>
    </div>
    @push('scripts')
    <script>
        // Tous les rendez-vous localisés sur une même carte, cadrée pour les montrer tous.
        (() => {
            const element = document.querySelector('[data-carte-evenements]');
            const points = JSON.parse(element.dataset.points);
            const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
            const carte = carteOsm(element, [points[0].lat, points[0].lng], 12);
            const reperes = points.map(p => L.marker([p.lat, p.lng]).addTo(carte).bindPopup(
                `<strong>${esc(p.titre)}</strong><br><span>${esc(p.type)} · ${esc(p.association)}</span><br><span>${esc(p.horaire)}</span><br><span>${esc(p.lieu)}</span><br><a href="${esc(p.url)}">Voir l’événement</a>`
            ));
            if (reperes.length > 1) carte.fitBounds(L.featureGroup(reperes).getBounds().pad(0.2));
        })();
    </script>
    @endpush
    @endif
    <div class="grid gap-5 md:grid-cols-2">
        @forelse($evenements as $evenement)
        <a href="{{ route('evenements.show', $evenement) }}" class="flex gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="grid h-20 w-20 shrink-0 place-items-center rounded-2xl bg-rose-50 text-center text-rose-700">
                <div><p class="font-display text-2xl font-bold leading-none">{{ $evenement->debut->format('d') }}</p><p class="mt-1 text-xs font-semibold uppercase">{{ $evenement->debut->translatedFormat('M') }}</p></div>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2"><x-badge :label="$evenement->type->label()" :color="$evenement->type->color()" />@if($evenement->estCollecte() && $evenement->estComplet())<x-badge label="Complet" color="red" />@endif</div>
                <h2 class="mt-2 font-display text-lg font-bold">{{ $evenement->titre }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $evenement->horaire() }}</p>
                <p class="text-sm text-slate-500">{{ $evenement->lieu }} · {{ $evenement->association->nom }}</p>
            </div>
        </a>
        @empty
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center md:col-span-2">
            <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-rose-50 text-rose-600"><i data-lucide="calendar" class="h-7 w-7"></i></span>
            <h2 class="font-display text-xl font-bold">Aucun événement à venir</h2>
            <p class="mt-2 text-slate-500">Vous pouvez toujours proposer un don et le déposer au local de l'association.</p>
        </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $evenements->links() }}</div>
</section>
@endsection
