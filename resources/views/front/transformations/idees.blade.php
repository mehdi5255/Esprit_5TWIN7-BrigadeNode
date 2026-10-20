@extends('layouts.front')
@section('title', 'Que faire de ce vêtement ?')
@section('meta', 'Des idées de transformation proposées par l’IA à partir de la photo de votre vêtement.')
@section('content')
<section class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
    <a href="{{ route('transformations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes transformations</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8">
            <span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-violet-50 text-violet-700"><i data-lucide="wand-sparkles" class="h-6 w-6"></i></span>
            <h1 class="font-display text-3xl font-bold">Que faire de ce vêtement ?</h1>
            <p class="mt-2 text-slate-500">Choisissez un vêtement : l’IA regarde sa photo et sa composition, propose trois projets de transformation et vous montre les modèles des ateliers qui s’en rapprochent.</p>
        </div>
        @if($vetements->isEmpty())
            @include('partials.front.aucun-vetement')
        @else
        <form data-idees data-url="{{ route('transformations.idees.generer') }}" data-apercu-url="{{ route('transformations.apercu') }}" data-demande-url="{{ route('transformations.create') }}" class="space-y-6">
            @csrf
            @include('partials.front.choix-vetement')
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:opacity-60"><i data-lucide="sparkles" class="h-4 w-4"></i>Trouver des idées</button>
        </form>
        <div data-resultats class="mt-8 hidden"></div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Assistant d'upcycling : idées (IA) pour le vêtement choisi, aperçu du résultat, puis demande préremplie.
    const formIdees = document.querySelector('[data-idees]');
    if (formIdees) {
        const resultats = document.querySelector('[data-resultats]');
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        const jeton = formIdees.querySelector('[name=_token]').value;
        const poster = async (url, donnees) => {
            const corps = new FormData();
            corps.append('_token', jeton);
            Object.entries(donnees).forEach(([cle, valeur]) => corps.append(cle, valeur));
            const reponse = await fetch(url, { method: 'POST', body: corps, headers: { Accept: 'application/json' } });
            if (!reponse.ok) throw new Error(reponse.status === 429 ? 'Trop de demandes : réessayez dans une minute.' : 'Le service est indisponible pour le moment.');
            return reponse.json();
        };
        const lienDemande = params => formIdees.dataset.demandeUrl + '?' + new URLSearchParams(params);
        const couleurs = { facile: 'bg-emerald-50 text-emerald-700', moyenne: 'bg-amber-50 text-amber-700', 'avancée': 'bg-rose-50 text-rose-700' };

        const carteInspiration = (i, vetementId) => `
            <a href="${lienDemande({ inspiration: i.id, vetement: vetementId })}" class="flex items-center gap-3 rounded-xl border border-violet-100 bg-violet-50/60 p-2 hover:border-violet-300">
                ${i.photo ? `<img src="${esc(i.photo)}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover">` : '<span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-violet-600">✦</span>'}
                <span class="min-w-0 text-xs"><span class="block truncate font-semibold text-slate-800">${esc(i.titre)}</span><span class="text-slate-500">${esc(i.atelier)} · ${esc(i.prix)}</span></span>
            </a>`;

        formIdees.addEventListener('submit', async (e) => {
            e.preventDefault();
            const choix = formIdees.querySelector('input[name=vetement_id]:checked');
            if (!choix) return;
            const bouton = formIdees.querySelector('button[type=submit]');
            const photoVetement = choix.closest('label').querySelector('img')?.src ?? null;
            bouton.disabled = true;
            resultats.classList.remove('hidden');
            resultats.innerHTML = '<p class="flex items-center gap-2 text-sm text-violet-700"><i data-lucide="loader-circle" class="h-4 w-4 animate-spin"></i>L’IA examine votre vêtement…</p>';
            window.lucide?.createIcons();
            try {
                const r = await poster(formIdees.dataset.url, { vetement_id: choix.value });
                if (!r.idees.length) {
                    resultats.innerHTML = `<p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Aucune idée disponible pour ce vêtement pour le moment. <a class="font-semibold text-violet-700 underline" href="${lienDemande({ vetement: choix.value })}">Décrire mon propre projet</a></p>`;
                    return;
                }
                const entete = r.base === 'gemini'
                    ? '<p class="mb-4 flex items-center gap-2 text-sm font-semibold text-violet-700"><i data-lucide="sparkles" class="h-4 w-4"></i>Idées proposées par Gemini pour ce vêtement</p>'
                    : '<p class="mb-4 text-sm text-slate-500">L’assistant IA est indisponible : voici les modèles des ateliers pour ce type de vêtement.</p>';
                resultats.innerHTML = entete + '<div class="grid gap-5 md:grid-cols-3">' + r.idees.map((idee, n) => `
                    <article data-idee="${n}" class="flex flex-col rounded-2xl border border-slate-200 p-5">
                        <h2 class="font-display text-lg font-bold">${esc(idee.titre)}</h2>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold">
                            ${idee.difficulte ? `<span class="rounded-full px-2.5 py-1 ${couleurs[idee.difficulte] ?? 'bg-slate-100'}">Difficulté ${esc(idee.difficulte)}</span>` : ''}
                            ${idee.temps ? `<span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">${esc(idee.temps)}</span>` : ''}
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-600">${esc(idee.description)}</p>
                        ${idee.matieres ? `<p class="mt-2 text-xs text-slate-500"><span class="font-semibold">Matière réutilisée :</span> ${esc(idee.matieres)}</p>` : ''}
                        ${idee.inspirations.length ? `<p class="mt-4 text-xs font-semibold uppercase tracking-wide text-violet-600">Modèles proches en atelier</p><div class="mt-2 space-y-2">${idee.inspirations.map(i => carteInspiration(i, choix.value)).join('')}</div>` : ''}
                        <div data-zone-apercu class="mt-4 hidden"></div>
                        <div class="mt-auto flex flex-col gap-2 pt-5">
                            <button type="button" data-apercu class="inline-flex items-center justify-center gap-2 rounded-xl border border-violet-200 px-4 py-2.5 text-sm font-semibold text-violet-700 hover:bg-violet-50 disabled:opacity-60"><i data-lucide="image" class="h-4 w-4"></i>Voir l’aperçu (IA)</button>
                            <a data-demander href="${lienDemande({ vetement: choix.value, idee: idee.titre + ' : ' + idee.description })}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Demander ce projet</a>
                        </div>
                    </article>`).join('') + '</div>';
                window.lucide?.createIcons();

                resultats.querySelectorAll('[data-idee]').forEach(carte => {
                    const idee = r.idees[carte.dataset.idee];
                    const texte = idee.titre + ' : ' + idee.description;
                    carte.querySelector('[data-apercu]').addEventListener('click', async (ev) => {
                        const boutonApercu = ev.currentTarget;
                        const zone = carte.querySelector('[data-zone-apercu]');
                        boutonApercu.disabled = true;
                        zone.classList.remove('hidden');
                        zone.innerHTML = '<p class="text-xs text-violet-700">Génération de l’aperçu… (jusqu’à 30 s)</p>';
                        try {
                            const a = await poster(formIdees.dataset.apercuUrl, { vetement_id: choix.value, idee: texte });
                            if (a.chemin) {
                                zone.innerHTML = `<div class="grid grid-cols-2 gap-2">
                                    <figure>${photoVetement ? `<img src="${esc(photoVetement)}" alt="" class="aspect-square w-full rounded-xl object-cover">` : '<div class="aspect-square rounded-xl bg-slate-100"></div>'}<figcaption class="mt-1 text-center text-xs text-slate-500">Avant</figcaption></figure>
                                    <figure><img src="${esc(a.url)}" alt="Aperçu généré" class="aspect-square w-full rounded-xl object-cover"><figcaption class="mt-1 text-center text-xs text-violet-600">Après (${a.base === 'gemini' ? 'aperçu IA' : 'illustration IA'})</figcaption></figure>
                                </div>${a.base === 'illustration' ? '<p class="mt-2 text-xs text-slate-500">Illustration indicative dessinée d’après la description de votre vêtement : le résultat réel dépendra du tissu et de l’atelier.</p>' : ''}`;
                                carte.querySelector('[data-demander]').href = lienDemande({ vetement: choix.value, idee: texte, apercu: a.chemin });
                            } else if (a.base === 'inspiration') {
                                zone.innerHTML = `<p class="mb-2 text-xs text-slate-500">L’aperçu IA n’est pas disponible pour le moment. Voici une réalisation proche d’un atelier :</p><img src="${esc(a.url)}" alt="" class="aspect-square w-full rounded-xl object-cover"><p class="mt-1 text-center text-xs text-slate-500">${esc(a.inspiration.titre)} — ${esc(a.inspiration.atelier)}</p>`;
                            } else {
                                zone.innerHTML = '<p class="text-xs text-slate-500">L’aperçu IA n’est pas disponible pour le moment.</p>';
                            }
                        } catch (err) {
                            zone.innerHTML = `<p class="text-xs text-red-600">${esc(err.message)}</p>`;
                        } finally {
                            boutonApercu.disabled = false;
                        }
                    });
                });
            } catch (err) {
                resultats.innerHTML = `<p class="rounded-2xl bg-red-50 p-4 text-sm text-red-700">${esc(err.message)}</p>`;
            } finally {
                bouton.disabled = false;
            }
        });
    }
</script>
@endpush
