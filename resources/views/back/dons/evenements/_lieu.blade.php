{{-- Lieu de l'événement : adresse saisie ou cherchée, puis position précise sur la carte. Attend $evenement. --}}
@include('partials.carte.leaflet')
@php
    $latitude = old('latitude', $evenement->latitude);
    $longitude = old('longitude', $evenement->longitude);
@endphp
<div data-choix-lieu data-recherche="{{ route('association.geocodage.rechercher') }}" data-adresse="{{ route('association.geocodage.adresse') }}" class="space-y-3">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1"><x-champ label="Lieu" name="lieu" :value="$evenement->lieu" :required="true" placeholder="Ex. Place de la Mairie, Montreuil" /></div>
        <button type="button" data-chercher class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-900 disabled:opacity-60"><i data-lucide="search" class="h-4 w-4"></i>Chercher sur la carte</button>
    </div>
    <ul data-resultats class="hidden divide-y divide-slate-100 overflow-hidden rounded-xl text-sm ring-1 ring-slate-200"></ul>
    <div data-carte class="relative z-0 h-72 w-full overflow-hidden rounded-2xl ring-1 ring-slate-200"></div>
    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
        <p data-etat class="text-slate-500"></p>
        <button type="button" data-effacer class="font-semibold text-slate-500 hover:text-red-600">Retirer la position</button>
    </div>
    <input type="hidden" name="latitude" value="{{ $latitude }}">
    <input type="hidden" name="longitude" value="{{ $longitude }}">
    @error('latitude')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
</div>

@push('scripts')
<script>
    // Placement du lieu : recherche d'adresse (OpenStreetMap), clic sur la carte ou repère déplacé.
    // L'adresse du point choisi peut remplir le champ « Lieu » ; les coordonnées partent dans des champs cachés.
    (() => {
        const bloc = document.querySelector('[data-choix-lieu]');
        if (!bloc) return;
        const champLieu = bloc.querySelector('[name=lieu]');
        const champLat = bloc.querySelector('[name=latitude]');
        const champLng = bloc.querySelector('[name=longitude]');
        const resultats = bloc.querySelector('[data-resultats]');
        const etat = bloc.querySelector('[data-etat]');
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

        const depart = champLat.value && champLng.value ? [Number(champLat.value), Number(champLng.value)] : null;
        const carte = carteOsm(bloc.querySelector('[data-carte]'), depart ?? [48.8566, 2.3522], depart ? 16 : 11);
        let repere = null;

        const afficherEtat = () => {
            etat.textContent = champLat.value
                ? `Position enregistrée : ${Number(champLat.value).toFixed(5)}, ${Number(champLng.value).toFixed(5)}. Déplacez le repère pour l’ajuster.`
                : 'Aucune position : cherchez l’adresse ou cliquez sur la carte pour placer le lieu.';
        };

        const placer = (latitude, longitude, { centrer = true } = {}) => {
            champLat.value = latitude.toFixed(7);
            champLng.value = longitude.toFixed(7);
            if (!repere) {
                repere = L.marker([latitude, longitude], { draggable: true }).addTo(carte);
                repere.on('dragend', () => { const p = repere.getLatLng(); placer(p.lat, p.lng, { centrer: false }); proposerAdresse(p.lat, p.lng); });
            } else {
                repere.setLatLng([latitude, longitude]);
            }
            if (centrer) carte.setView([latitude, longitude], Math.max(carte.getZoom(), 16));
            afficherEtat();
        };

        // Point choisi à la main : on propose l'adresse correspondante, sans écraser une saisie volontaire.
        const proposerAdresse = async (latitude, longitude) => {
            try {
                const r = await fetch(`${bloc.dataset.adresse}?lat=${latitude}&lng=${longitude}`, { headers: { Accept: 'application/json' } }).then(rep => rep.json());
                if (r.adresse) {
                    etat.innerHTML = `${esc(etat.textContent)} <button type="button" class="font-semibold text-forest-700 underline" data-utiliser-adresse>Utiliser l’adresse : ${esc(r.adresse)}</button>`;
                    etat.querySelector('[data-utiliser-adresse]').addEventListener('click', () => { champLieu.value = r.adresse; afficherEtat(); });
                }
            } catch {}
        };

        if (depart) placer(...depart);
        afficherEtat();

        carte.on('click', e => { placer(e.latlng.lat, e.latlng.lng, { centrer: false }); proposerAdresse(e.latlng.lat, e.latlng.lng); });

        const chercher = async () => {
            const bouton = bloc.querySelector('[data-chercher]');
            const recherche = champLieu.value.trim();
            resultats.classList.remove('hidden');
            if (recherche.length < 3) { resultats.innerHTML = '<li class="p-3 text-slate-500">Saisissez au moins 3 caractères.</li>'; return; }
            bouton.disabled = true;
            resultats.innerHTML = '<li class="p-3 text-slate-500">Recherche…</li>';
            try {
                const lieux = await fetch(`${bloc.dataset.recherche}?q=${encodeURIComponent(recherche)}`, { headers: { Accept: 'application/json' } }).then(r => r.json());
                if (!Array.isArray(lieux) || !lieux.length) { resultats.innerHTML = '<li class="p-3 text-slate-500">Aucune adresse trouvée : précisez la ville, ou cliquez directement sur la carte.</li>'; return; }
                resultats.innerHTML = lieux.map((l, i) => `<li><button type="button" data-lieu="${i}" class="flex w-full items-start gap-2 p-3 text-left hover:bg-forest-50"><span class="text-forest-600">📍</span><span>${esc(l.adresse)}</span></button></li>`).join('');
                resultats.querySelectorAll('[data-lieu]').forEach(b => b.addEventListener('click', () => {
                    const l = lieux[b.dataset.lieu];
                    champLieu.value = l.adresse;
                    placer(l.latitude, l.longitude);
                    resultats.classList.add('hidden');
                }));
            } catch {
                resultats.innerHTML = '<li class="p-3 text-slate-500">Recherche indisponible : cliquez directement sur la carte.</li>';
            } finally {
                bouton.disabled = false;
            }
        };
        bloc.querySelector('[data-chercher]').addEventListener('click', chercher);
        // Entrée dans le champ Lieu : chercher plutôt que soumettre le formulaire.
        champLieu.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); chercher(); } });

        bloc.querySelector('[data-effacer]').addEventListener('click', () => {
            champLat.value = ''; champLng.value = '';
            if (repere) { repere.remove(); repere = null; }
            afficherEtat();
        });
    })();
</script>
@endpush
