@csrf
@php
    // Prix du marché pour un service existant (en création, il arrive avec l'assistant).
    $assistant = new \App\Models\Ateliers\AssistantService;
    $marche = $service->exists && $service->nom
        ? $assistant->marche($assistant->similaires($service->nom, $service->type, auth()->user()->atelier))
        : null;
@endphp
<section class="max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-service-form data-assistant="{{ route('atelier.services.assistant') }}" data-marche='@json($marche)'>
    <div class="grid gap-5 sm:grid-cols-2">
        <x-champ label="Type" name="type" type="select" :required="true" :value="$service->type" :options="collect(\App\Enums\TypeService::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])" />
        <x-champ label="Nom du service" name="nom" :value="$service->nom" :required="true" placeholder="Ex. Ourlet de pantalon" />
    </div>

    {{-- Assistant IA : description, durée, délai et prix conseillé d'après les services similaires des autres ateliers --}}
    <div class="rounded-xl border border-violet-200 bg-violet-50/60 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-violet-900"><span class="font-semibold">Assistant IA</span> · tapez le nom du service, l’IA propose le reste.</p>
            <button type="button" data-remplir class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-60"><i data-lucide="wand-sparkles" class="h-4 w-4"></i>Remplir avec l’IA</button>
        </div>
        <div data-resultat-assistant class="mt-3 hidden text-sm"></div>
    </div>

    <x-champ label="Description" name="description" type="textarea" :value="$service->description" />
    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <x-champ label="Prix (€)" name="prix" type="number" :value="$service->prix" min="0" step="0.5" help="Vide = sur devis." />
            <p data-positionnement class="mt-1.5 hidden text-xs font-semibold"></p>
        </div>
        <x-champ label="Durée (min)" name="duree" type="number" :value="$service->duree" min="1" />
        <x-champ label="Délai (jours)" name="delai_jours" type="number" :value="$service->delai_jours" min="0" />
    </div>
    <p data-marche class="hidden rounded-xl bg-slate-50 p-3 text-xs text-slate-600"></p>
    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="actif" value="1" @checked(old('actif', $service->actif ?? true)) class="rounded border-slate-300 text-forest-600 focus:ring-forest-500">Service actif (visible des particuliers)</label>
    <div class="flex gap-3">
        <x-button :href="route('atelier.services.index')" label="Annuler" variant="secondary" />
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>{{ $submit }}</button>
    </div>
</section>

@push('scripts')
<script>
    // Assistant de création de service (IA) et positionnement du prix par rapport au marché de la plateforme.
    (() => {
        const bloc = document.querySelector('[data-service-form]');
        if (!bloc) return;
        const form = bloc.closest('form');
        const champ = nom => form.querySelector(`[name=${nom}]`);
        const zone = bloc.querySelector('[data-resultat-assistant]');
        const indicateur = bloc.querySelector('[data-positionnement]');
        const infoMarche = bloc.querySelector('[data-marche]');
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        const euros = n => Number(n).toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' €';
        let marche = JSON.parse(bloc.dataset.marche || 'null');

        // Écart du prix saisi avec la moyenne des services similaires (au-delà de 20 % : signalé).
        const positionner = () => {
            const prix = parseFloat(champ('prix').value);
            if (!marche || isNaN(prix) || !marche.moyenne) { indicateur.classList.add('hidden'); return; }
            const ecart = Math.round((prix - marche.moyenne) / marche.moyenne * 100);
            const [texte, couleur] = ecart > 20 ? [`${ecart} % plus cher que la moyenne`, 'text-amber-700']
                : ecart < -20 ? [`${Math.abs(ecart)} % moins cher que la moyenne`, 'text-blue-700']
                : ['Dans la moyenne du marché', 'text-emerald-700'];
            indicateur.className = `mt-1.5 text-xs font-semibold ${couleur}`;
            indicateur.textContent = texte;
        };
        const afficherMarche = () => {
            infoMarche.classList.toggle('hidden', !marche);
            if (marche) infoMarche.innerHTML = `<span class="font-semibold">Sur la plateforme :</span> ${marche.nombre} service${marche.nombre > 1 ? 's' : ''} similaire${marche.nombre > 1 ? 's' : ''} chez ${marche.ateliers} atelier${marche.ateliers > 1 ? 's' : ''}, de ${euros(marche.min)} à ${euros(marche.max)} · moyenne <span class="font-semibold">${euros(marche.moyenne)}</span>`;
            positionner();
        };
        champ('prix').addEventListener('input', positionner);
        afficherMarche();

        bloc.querySelector('[data-remplir]').addEventListener('click', async (e) => {
            const bouton = e.currentTarget;
            const nom = champ('nom').value.trim();
            zone.classList.remove('hidden');
            if (nom.length < 3) { zone.innerHTML = '<p class="text-violet-900">Tapez d’abord le nom du service (ex. « Remplacement de fermeture éclair »).</p>'; return; }
            const corps = new FormData();
            corps.append('_token', form.querySelector('[name=_token]').value);
            corps.append('nom', nom);
            corps.append('type', champ('type').value);
            bouton.disabled = true;
            zone.innerHTML = '<p class="text-violet-800">L’IA prépare la fiche du service…</p>';
            try {
                const reponse = await fetch(bloc.dataset.assistant, { method: 'POST', body: corps, headers: { Accept: 'application/json' } });
                const r = await reponse.json();
                if (!reponse.ok) { zone.innerHTML = `<p class="text-amber-800">${esc(Object.values(r.errors ?? {})[0]?.[0] ?? 'Assistant indisponible.')}</p>`; return; }
                // Les champs déjà remplis par l'atelier ne sont pas écrasés.
                const remplir = (nomChamp, valeur) => { if (valeur !== null && valeur !== undefined && valeur !== '' && !champ(nomChamp).value.trim()) champ(nomChamp).value = valeur; };
                remplir('description', r.description);
                remplir('duree', r.duree);
                remplir('delai_jours', r.delai_jours);
                remplir('prix', r.prix);
                marche = r.marche;
                afficherMarche();
                zone.innerHTML = r.base === 'gemini'
                    ? `<p class="text-violet-900"><span class="font-semibold">Fiche préremplie par l’IA</span>${r.prix !== null ? ` · prix conseillé <span class="font-semibold">${euros(r.prix)}</span>` : ''}.</p>${r.explication ? `<p class="mt-1 text-slate-600">${esc(r.explication)}</p>` : ''}<p class="mt-1 text-xs text-violet-700">Vérifiez et ajustez avant d’enregistrer. Les champs que vous aviez déjà remplis ont été conservés.</p>`
                    : r.base === 'marche'
                        ? '<p class="text-slate-600">L’IA est indisponible : prix, durée et délai proposés d’après les services similaires de la plateforme.</p>'
                        : '<p class="text-slate-600">Aucun service similaire sur la plateforme et IA indisponible : fixez les valeurs vous-même.</p>';
            } catch {
                zone.innerHTML = '<p class="text-amber-800">Assistant indisponible pour le moment.</p>';
            } finally {
                bouton.disabled = false;
            }
        });
    })();
</script>
@endpush
