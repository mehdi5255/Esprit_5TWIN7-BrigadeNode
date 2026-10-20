@extends('layouts.front')
@section('title', 'Faire un don')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route('dons.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes dons</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-rose-50 text-rose-600"><i data-lucide="heart-handshake" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Faire un don</h1><p class="mt-2 text-slate-500">Choisissez une association et, si possible, l'un de ses besoins actuels.</p></div>
        @if($vetements->isEmpty())
            @include('partials.front.aucun-vetement')
        @elseif($associations->isEmpty())
            <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Aucune association n'accepte de dons pour le moment.</p>
        @else
        <form method="POST" action="{{ route('dons.store') }}" class="space-y-6" data-don data-controle="{{ route('dons.controle') }}">
            @csrf
            @include('partials.front.choix-vetement')
            <x-champ label="Association" name="association_id" type="select" :required="true" vide="Choisir une association…" :value="$associationChoisie" :options="$associations->mapWithKeys(fn ($a) => [$a->id => $a->nom.($a->ville ? ' ('.$a->ville.')' : '')])" />
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Besoin concerné</span>
                <select name="besoin_id" class="w-full rounded-xl border {{ $errors->has('besoin_id') ? 'border-red-400' : 'border-slate-200' }} bg-white px-4 py-3 text-sm outline-none focus:border-forest-500 focus:ring-4 focus:ring-forest-100">
                    <option value="">Don libre (pas de besoin précis)</option>
                    @foreach($associations as $association)
                        @foreach($association->besoinsActifs as $besoin)
                        <option value="{{ $besoin->id }}" data-association="{{ $association->id }}" data-categorie="{{ $besoin->categorie_id }}" data-categorie-libelle="{{ $besoin->categorie?->libelle }}" data-titre="{{ $besoin->titre }}" @selected(old('besoin_id', $besoinChoisi) == $besoin->id)>{{ $besoin->titre }}{{ $besoin->categorie ? ' ('.$besoin->categorie->libelle.')' : '' }} — {{ $besoin->priorite->label() }}</option>
                        @endforeach
                    @endforeach
                </select>
                @error('besoin_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@else<span class="mt-1.5 block text-xs text-slate-400">Seuls les besoins de l'association choisie sont proposés. Chaque besoin indique la catégorie de vêtements attendue.</span>@enderror
            </label>
            <p data-alerte-besoin class="hidden -mt-3 flex items-start gap-2 rounded-xl bg-red-50 p-3 text-sm text-red-800"></p>
            {{-- Contrôle qualité IA : le vêtement est-il donnable tel quel ? Sinon, réparation ou transformation. --}}
            <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5">
                <p class="flex items-center gap-2 text-sm font-semibold text-rose-900"><i data-lucide="scan-eye" class="h-4 w-4"></i>Mon vêtement est-il donnable ?</p>
                <p class="mt-1 text-xs text-rose-800">L’IA examine la photo du vêtement (ou une photo récente que vous ajoutez) et vous dit s’il peut être donné tel quel.</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <input type="file" accept="image/*" data-photo-controle class="block w-full max-w-xs text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-semibold file:text-rose-700">
                    <button type="button" data-verifier class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60"><i data-lucide="sparkles" class="h-4 w-4"></i>Vérifier avec l’IA</button>
                </div>
                <input type="hidden" name="controle_ia" value="{{ old('controle_ia') }}">
                <div data-resultat-controle class="mt-3 hidden text-sm"></div>
            </div>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Où déposer votre don ?</span>
                <select name="collecte_id" class="w-full rounded-xl border {{ $errors->has('collecte_id') ? 'border-red-400' : 'border-slate-200' }} bg-white px-4 py-3 text-sm outline-none focus:border-forest-500 focus:ring-4 focus:ring-forest-100">
                    <option value="">Au local de l'association</option>
                    @foreach($collectes as $collecte)
                    <option value="{{ $collecte->id }}" data-association="{{ $collecte->association_id }}" @selected(old('collecte_id', $collecteChoisie) == $collecte->id) @disabled($collecte->estComplet())>Collecte : {{ $collecte->titre }} — {{ $collecte->debut->translatedFormat('d M, H:i') }} — {{ $collecte->lieu }}{{ $collecte->estComplet() ? ' (complet)' : ($collecte->placesRestantes() !== null ? ' ('.$collecte->placesRestantes().' places)' : '') }}</option>
                    @endforeach
                </select>
                @error('collecte_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@else<span class="mt-1.5 block text-xs text-slate-400">Seules les collectes à venir de l'association choisie sont proposées. <a href="{{ route('evenements.index') }}" class="font-semibold text-forest-700 hover:underline">Voir l'agenda</a></span>@enderror
            </label>
            <x-champ label="Message à l’association" name="message" type="textarea" placeholder="Ex. Manteau porté deux hivers, lavé et en très bon état." />
            <div class="flex justify-end gap-3">
                <x-button :href="route('dons.index')" label="Annuler" variant="secondary" />
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Proposer le don</button>
            </div>
        </form>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Ne propose que les besoins et les collectes de l'association sélectionnée ;
    // un besoin d'une autre catégorie que le vêtement choisi est grisé (règle aussi vérifiée à l'envoi).
    const formDon = document.querySelector('[data-don]');
    if (formDon) {
        const association = formDon.querySelector('[name=association_id]');
        const besoinChoisi = formDon.querySelector('[name=besoin_id]');
        const alerteBesoin = formDon.querySelector('[data-alerte-besoin]');
        const filtrer = () => {
            ['besoin_id', 'collecte_id'].forEach(nom => {
                const liste = formDon.querySelector(`[name=${nom}]`);
                liste.querySelectorAll('option[data-association]').forEach(option => {
                    option.hidden = option.dataset.association !== association.value;
                    if (option.hidden && option.selected) liste.value = '';
                });
            });
            verifierBesoin();
        };

        // Le besoin choisi reste sélectionné : s'il attend une autre catégorie que le vêtement, on le dit tout de suite
        // (l'envoi sera refusé tant qu'on ne change pas de besoin ou de vêtement).
        const verifierBesoin = () => {
            const vetement = formDon.querySelector('input[name=vetement_id]:checked');
            const option = besoinChoisi.selectedOptions[0];
            const incompatible = vetement && option?.dataset.categorie && option.dataset.categorie !== vetement.dataset.categorie;
            alerteBesoin.classList.toggle('hidden', !incompatible);
            if (incompatible) {
                const nom = vetement.closest('label').querySelector('.font-semibold')?.textContent.trim() ?? 'votre vêtement';
                alerteBesoin.textContent = `Le besoin « ${option.dataset.titre} » attend des articles de la catégorie « ${option.dataset.categorieLibelle} » : « ${nom} » n’y correspond pas. Choisissez un autre vêtement, un autre besoin, ou un don libre.`;
            }
        };
        association.addEventListener('change', filtrer);
        besoinChoisi.addEventListener('change', verifierBesoin);
        formDon.querySelectorAll('input[name=vetement_id]').forEach(r => r.addEventListener('change', verifierBesoin));
        filtrer();

        // Contrôle qualité IA avant l'envoi : verdict, défauts vus, et alternatives (réparer, transformer).
        const zoneControle = formDon.querySelector('[data-resultat-controle]');
        const champControle = formDon.querySelector('[name=controle_ia]');
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

        // Un autre vêtement ou un autre besoin choisi : l'avis précédent ne vaut plus.
        [...formDon.querySelectorAll('input[name=vetement_id]'), formDon.querySelector('[name=besoin_id]'), association].forEach(champ => champ.addEventListener('change', () => {
            champControle.value = '';
            zoneControle.classList.add('hidden');
        }));

        formDon.querySelector('[data-verifier]').addEventListener('click', async (e) => {
            const bouton = e.currentTarget;
            const vetement = formDon.querySelector('input[name=vetement_id]:checked');
            zoneControle.classList.remove('hidden');
            if (!vetement) { zoneControle.innerHTML = '<p class="text-rose-900">Choisissez d’abord le vêtement à donner.</p>'; return; }

            const corps = new FormData();
            corps.append('_token', formDon.querySelector('[name=_token]').value);
            corps.append('vetement_id', vetement.value);
            const photo = formDon.querySelector('[data-photo-controle]').files[0];
            if (photo) corps.append('photo', photo);
            const besoin = formDon.querySelector('[name=besoin_id]').value;
            if (besoin) corps.append('besoin_id', besoin);

            bouton.disabled = true;
            zoneControle.innerHTML = '<p class="text-rose-800">Examen du vêtement…</p>';
            try {
                const reponse = await fetch(formDon.dataset.controle, { method: 'POST', body: corps, headers: { Accept: 'application/json' } });
                const r = await reponse.json();
                if (!reponse.ok) {
                    zoneControle.innerHTML = `<p class="text-amber-800">${esc(r.erreur ?? Object.values(r.errors ?? {})[0]?.[0] ?? 'Vérification impossible.')}</p>`;
                    return;
                }
                champControle.value = r.verdict;
                const styles = { donnable: 'bg-emerald-100 text-emerald-800', a_reparer: 'bg-amber-100 text-amber-800', a_transformer: 'bg-violet-100 text-violet-800', recyclage: 'bg-slate-200 text-slate-700' };
                const alternatives = {
                    a_reparer: `<a href="${esc(r.liens.reparation)}" class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700">Le faire réparer d’abord</a>`,
                    a_transformer: `<a href="${esc(r.liens.transformation)}" class="inline-flex items-center gap-1 rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white hover:bg-violet-700">Voir des idées de transformation</a>`,
                    recyclage: '<p class="text-xs text-slate-600">Vous pouvez quand même le proposer : l’association pourra le confier au recyclage des fibres plutôt qu’à un bénéficiaire.</p>',
                };
                // Mauvais vêtement pour le besoin choisi : c'est le message principal, avant l'état du vêtement.
                const horsBesoin = r.besoin && !r.besoin.correspond;
                zoneControle.innerHTML = `<div class="rounded-xl bg-white p-4">
                    ${horsBesoin ? `<div class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-red-800">
                        <p class="font-semibold">Ce n’est pas le bon vêtement pour le besoin « ${esc(r.besoin.titre)} ».</p>
                        <p class="mt-1">${esc(r.besoin.remarque)}</p>
                    </div>` : ''}
                    <p><span class="rounded-full px-2.5 py-1 text-xs font-semibold ${styles[r.verdict] ?? ''}">${esc(r.verdict_libelle)}${horsBesoin ? ' (en don libre ou pour un autre besoin)' : ''}</span></p>
                    <p class="mt-2 text-slate-700">${esc(r.etat_observe)}</p>
                    ${r.defauts.length ? `<ul class="mt-2 list-disc pl-5 text-slate-600">${r.defauts.map(d => `<li>${esc(d)}</li>`).join('')}</ul>` : ''}
                    <p class="mt-2 text-slate-600"><span class="font-semibold">Conseil :</span> ${esc(r.conseil)}</p>
                    ${alternatives[r.verdict] ? `<div class="mt-3">${alternatives[r.verdict]}</div>` : ''}
                    ${r.besoin?.correspond ? `<p class="mt-3 rounded-lg bg-emerald-50 p-2 text-emerald-800"><span class="font-semibold">Besoin « ${esc(r.besoin.titre)} » :</span> ${esc(r.besoin.remarque || 'votre vêtement y correspond.')}</p>` : ''}
                    <p class="mt-2 text-xs text-slate-400">Avis indicatif : c’est vous qui décidez, et l’association vérifiera à la réception.</p>
                </div>`;
            } catch {
                zoneControle.innerHTML = '<p class="text-amber-800">La vérification automatique est indisponible pour le moment.</p>';
            } finally {
                bouton.disabled = false;
            }
        });
    }
</script>
@endpush
