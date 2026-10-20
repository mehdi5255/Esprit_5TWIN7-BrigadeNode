{{-- Contrôle qualité à la réception : orientation du don, proposée par l'IA et validée par l'association. Attend $demande, $espace. --}}
@use('App\Enums\OrientationDon')
@use('App\Enums\StatutDon')
@php
    $modifiable = $espace === 'association' && in_array($demande->statut, [StatutDon::DEPOT_REMIS, StatutDon::EN_TRAITEMENT], true);
@endphp
@if($modifiable || $demande->orientation)
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-orientation-don>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-lg font-bold">Contrôle qualité et orientation</h2>
        @if($demande->orientation)<x-badge :label="$demande->orientation->label()" :color="$demande->orientation->color()" />@endif
    </div>
    @if($demande->orientation)
        <p class="mt-2 text-sm text-slate-500">{{ $demande->orientation->description() }}</p>
        @if($demande->orientation_motif)<p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $demande->orientation_motif }}</p>@endif
    @endif

    @if($modifiable)
    <div class="mt-5 rounded-xl bg-violet-50 p-4">
        <p class="text-sm text-violet-900">Photographiez le vêtement reçu : l’IA propose son orientation et le besoin auquel il répond. Vous validez.</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <input type="file" accept="image/*" data-photo-orientation class="block w-full max-w-xs text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-semibold file:text-violet-700">
            <button type="button" data-analyser-orientation data-url="{{ route('association.dons.orientation.analyse', $demande) }}" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-60"><i data-lucide="sparkles" class="h-4 w-4"></i>Analyser (IA)</button>
        </div>
        <p class="mt-1 text-xs text-violet-700">Sans nouvelle photo, l’IA utilise celle du donateur.</p>
        <div data-resultat-orientation class="mt-3 hidden text-sm"></div>
    </div>

    <form method="POST" action="{{ route('association.dons.orientation', $demande) }}" class="mt-5 space-y-4">@csrf
        <fieldset>
            <legend class="mb-2 text-sm font-semibold text-slate-700">Orientation <span class="text-red-500">*</span></legend>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach(OrientationDon::cases() as $orientation)
                <label class="flex cursor-pointer flex-col gap-1 rounded-xl border border-slate-200 p-3 text-sm has-[:checked]:border-forest-500 has-[:checked]:bg-forest-50">
                    <span class="flex items-center gap-2 font-semibold"><input type="radio" name="orientation" value="{{ $orientation->value }}" required @checked(old('orientation', $demande->orientation?->value) === $orientation->value) class="text-forest-600 focus:ring-forest-500"><i data-lucide="{{ $orientation->icone() }}" class="h-4 w-4"></i>{{ $orientation->label() }}</span>
                    <span class="text-xs text-slate-500">{{ $orientation->description() }}</span>
                </label>
                @endforeach
            </div>
            @error('orientation')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
        </fieldset>
        <x-champ label="Observations" name="orientation_motif" type="textarea" :rows="2" :value="$demande->orientation_motif" placeholder="Ex. Propre, aucune usure visible : correspond au besoin « Manteaux d'hiver »." />
        <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>Enregistrer l’orientation</button>
    </form>
    @endif
</section>

@if($modifiable)
@push('scripts')
<script>
    // Orientation proposée par l'IA : présélectionne le choix et remplit les observations, l'association valide.
    const blocOrientation = document.querySelector('[data-orientation-don]');
    blocOrientation?.querySelector('[data-analyser-orientation]').addEventListener('click', async (e) => {
        const bouton = e.currentTarget;
        const zone = blocOrientation.querySelector('[data-resultat-orientation]');
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        const corps = new FormData();
        corps.append('_token', blocOrientation.querySelector('[name=_token]').value);
        const photo = blocOrientation.querySelector('[data-photo-orientation]').files[0];
        if (photo) corps.append('photo', photo);

        bouton.disabled = true;
        zone.classList.remove('hidden');
        zone.innerHTML = '<p class="text-violet-800">Analyse du vêtement…</p>';
        try {
            const reponse = await fetch(bouton.dataset.url, { method: 'POST', body: corps, headers: { Accept: 'application/json' } });
            const r = await reponse.json();
            if (!reponse.ok) {
                zone.innerHTML = `<p class="text-amber-800">${esc(r.erreur ?? Object.values(r.errors ?? {})[0]?.[0] ?? 'Analyse impossible.')}</p>`;
                return;
            }
            blocOrientation.querySelector(`input[name=orientation][value="${r.orientation}"]`).checked = true;
            const phrase = t => t ? t.charAt(0).toUpperCase() + t.slice(1).replace(/[.!]?$/, '.') : '';
            const observations = [phrase(r.etat_observe), r.defauts.length ? 'Défauts : ' + r.defauts.join(', ') + '.' : '', r.besoin ? `Répond au besoin « ${r.besoin.titre} ».` : ''].filter(Boolean).join(' ');
            blocOrientation.querySelector('[name=orientation_motif]').value = observations;
            zone.innerHTML = `<div class="rounded-xl bg-white p-4">
                <p class="font-semibold">Proposition : ${esc(r.orientation_libelle)}</p>
                <p class="mt-1 text-slate-600">${esc(r.justification)}</p>
                ${r.besoin ? `<p class="mt-2 text-slate-600"><span class="font-semibold">Besoin correspondant :</span> ${esc(r.besoin.titre)} (priorité ${esc(r.besoin.priorite)})</p>` : ''}
                <p class="mt-2 text-xs text-violet-700">Le choix a été présélectionné ci-dessous : vérifiez puis enregistrez.</p>
            </div>`;
        } catch {
            zone.innerHTML = '<p class="text-amber-800">L’analyse automatique est indisponible : choisissez l’orientation vous-même.</p>';
        } finally {
            bouton.disabled = false;
        }
    });
</script>
@endpush
@endif
@endif
