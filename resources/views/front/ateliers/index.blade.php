@extends('layouts.front')
@section('title', 'Ateliers partenaires')
@section('meta', 'Trouvez un atelier textile proche de chez vous.')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8"><p class="text-sm font-bold text-forest-600">Artisans engagés</p><h1 class="mt-2 font-display text-4xl font-bold">Trouver un atelier</h1><p class="mt-2 text-slate-500">Des savoir-faire locaux pour prendre soin de vos vêtements.</p></div>
    {{-- « Quel atelier pour mon besoin ? » : recommandation par l'IA --}}
    <section class="mb-8 rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-6" data-conseil-atelier data-url="{{ route('ateliers.conseil') }}">
        <h2 class="flex items-center gap-2 font-display text-xl font-bold"><i data-lucide="sparkles" class="h-5 w-5 text-violet-600"></i>Quel atelier pour mon besoin ?</h2>
        <p class="mt-1 text-sm text-slate-600">Décrivez votre besoin en une phrase : l’IA compare les services, spécialités, notes et villes des ateliers, et vous propose les plus adaptés.</p>
        <form class="mt-4 flex flex-col gap-3 sm:flex-row">
            @csrf
            <input name="besoin" required minlength="10" maxlength="500" class="min-w-0 flex-1 rounded-xl border border-violet-200 bg-white px-4 py-3 text-sm outline-none focus:border-violet-500 focus:ring-4 focus:ring-violet-100" placeholder="Ex. Ma veste en cuir est déchirée à l’épaule, je suis à Montreuil">
            <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white hover:bg-violet-700 disabled:opacity-60"><i data-lucide="wand-sparkles" class="h-4 w-4"></i>Trouver mon atelier</button>
        </form>
        <div data-resultats-conseil class="mt-5 hidden"></div>
    </section>

    <form method="GET" action="{{ route('ateliers.index') }}" class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3">
        <i data-lucide="map-pin" class="ml-2 h-5 w-5 text-forest-600"></i>
        <input name="q" value="{{ request('q') }}" class="min-w-0 flex-1 bg-transparent py-2 text-sm outline-none" placeholder="Ville, code postal, nom ou spécialité">
        @if(request('service'))<input type="hidden" name="service" value="{{ request('service') }}">@endif
        <button class="rounded-xl bg-forest-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-forest-700">Rechercher</button>
    </form>
    <div class="mb-8 flex flex-wrap gap-2 text-sm font-semibold">
        <a href="{{ route('ateliers.index', request()->except('service', 'page')) }}" class="rounded-full px-4 py-2 {{ request('service') ? 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' : 'bg-forest-600 text-white' }}">Tous les ateliers</a>
        @foreach(\App\Enums\TypeService::cases() as $type)
        <a href="{{ route('ateliers.index', ['service' => $type->value] + request()->except('service', 'page')) }}" class="rounded-full px-4 py-2 {{ request('service') === $type->value ? 'bg-forest-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' }}">{{ $type->label() }}</a>
        @endforeach
    </div>
    @if($ateliers->isEmpty())
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center">
            <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-forest-50 text-forest-700"><i data-lucide="store" class="h-7 w-7"></i></span>
            <h2 class="font-display text-xl font-bold">Aucun atelier trouvé</h2>
            <p class="mt-2 text-slate-500">Essayez une autre ville ou un autre service.</p>
        </div>
    @else
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach($ateliers as $atelier)
        <article class="flex flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
            <div class="flex items-start justify-between gap-3">
                @if($atelier->logoUrl())<img src="{{ $atelier->logoUrl() }}" alt="" class="h-14 w-14 rounded-2xl object-cover">
                @else<span class="grid h-14 w-14 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="store" class="h-7 w-7"></i></span>@endif
                <x-etoiles :note="$atelier->noteMoyenne()" :avis="$atelier->nombreAvis()" taille="h-3.5 w-3.5" />
            </div>
            <h2 class="mt-6 font-display text-xl font-bold"><a href="{{ route('ateliers.show', $atelier) }}" class="hover:text-forest-700">{{ $atelier->nom }}</a></h2>
            @if($atelier->specialite)<p class="mt-1 text-sm font-medium text-forest-700">{{ $atelier->specialite }}</p>@endif
            <p class="mt-2 flex items-center gap-2 text-sm text-slate-500"><i data-lucide="map-pin" class="h-4 w-4"></i>{{ trim($atelier->code_postal.' '.$atelier->ville) }}</p>
            <div class="mt-4 flex flex-wrap gap-2">@foreach($atelier->typesProposes() as $type)<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $type->label() }}</span>@endforeach</div>
            <a href="{{ route('ateliers.show', $atelier) }}" class="mt-auto inline-flex items-center gap-2 pt-6 text-sm font-bold text-forest-700">Voir l'atelier et ses tarifs <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
        </article>
        @endforeach
    </div>
    <div class="mt-8">{{ $ateliers->links() }}</div>
    @endif
</section>
@endsection

@push('scripts')
<script>
    // Recommandation d'ateliers : envoie le besoin, affiche les 3 ateliers proposés avec la raison et un lien de demande.
    (() => {
        const bloc = document.querySelector('[data-conseil-atelier]');
        if (!bloc) return;
        const form = bloc.querySelector('form');
        const zone = bloc.querySelector('[data-resultats-conseil]');
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        const etoiles = (note, avis) => note ? `<span class="text-amber-500">★</span> <span class="font-semibold">${String(note).replace('.', ',')}</span> <span class="text-slate-400">(${avis} avis)</span>` : '<span class="text-slate-400">Pas encore d’avis</span>';

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const bouton = form.querySelector('button');
            const corps = new FormData(form);
            bouton.disabled = true;
            zone.classList.remove('hidden');
            zone.innerHTML = '<p class="text-sm text-violet-700">Recherche des ateliers les plus adaptés…</p>';
            try {
                const reponse = await fetch(bloc.dataset.url, { method: 'POST', body: corps, headers: { Accept: 'application/json' } });
                const r = await reponse.json();
                if (!reponse.ok) {
                    zone.innerHTML = `<p class="text-sm text-amber-800">${esc(Object.values(r.errors ?? {})[0]?.[0] ?? (reponse.status === 429 ? 'Trop de recherches : réessayez dans une minute.' : 'Recherche impossible pour le moment.'))}</p>`;
                    return;
                }
                if (!r.ateliers.length) { zone.innerHTML = '<p class="text-sm text-slate-500">Aucun atelier ne correspond pour le moment : essayez de reformuler, ou parcourez la liste ci-dessous.</p>'; return; }
                zone.innerHTML = `<p class="mb-3 text-sm text-slate-600">${r.base === 'gemini' ? '<span class="font-semibold text-violet-700">Proposé par l’IA</span>' : 'Correspondances trouvées'} · service : <span class="font-semibold">${esc(r.type_libelle)}</span></p>
                    <div class="grid gap-4 md:grid-cols-3">${r.ateliers.map((a, i) => `
                        <article class="flex flex-col rounded-2xl border ${i === 0 ? 'border-violet-300 ring-2 ring-violet-100' : 'border-slate-200'} bg-white p-5">
                            <div class="flex items-center gap-3">
                                ${a.logo ? `<img src="${esc(a.logo)}" alt="" class="h-11 w-11 rounded-xl object-cover">` : '<span class="grid h-11 w-11 place-items-center rounded-xl bg-forest-100 text-forest-700">✂</span>'}
                                <div class="min-w-0"><h3 class="truncate font-display font-bold">${i === 0 ? '🥇 ' : ''}${esc(a.nom)}</h3><p class="text-xs text-slate-500">${esc(a.ville)}</p></div>
                            </div>
                            <p class="mt-2 text-sm">${etoiles(a.note, a.avis)}</p>
                            ${a.service ? `<p class="mt-2 text-sm"><span class="font-semibold">${esc(a.service.nom)}</span> · ${esc(a.service.prix)}${a.service.delai ? ' · ' + a.service.delai + ' j' : ''}</p>` : ''}
                            ${a.raison ? `<p class="mt-2 text-sm text-slate-600">${esc(a.raison)}</p>` : ''}
                            <div class="mt-auto flex flex-wrap gap-2 pt-4">
                                <a href="${esc(a.demander)}" class="inline-flex items-center gap-1 rounded-xl bg-forest-600 px-3 py-2 text-xs font-semibold text-white hover:bg-forest-700">Demander à cet atelier</a>
                                <a href="${esc(a.url)}" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 hover:text-forest-700">Voir l’atelier</a>
                            </div>
                        </article>`).join('')}</div>
                    ${r.conseil ? `<p class="mt-3 text-sm text-slate-600"><span class="font-semibold">Conseil :</span> ${esc(r.conseil)}</p>` : ''}`;
            } catch {
                zone.innerHTML = '<p class="text-sm text-amber-800">Recherche impossible pour le moment : parcourez la liste des ateliers ci-dessous.</p>';
            } finally {
                bouton.disabled = false;
            }
        });
    })();
</script>
@endpush
