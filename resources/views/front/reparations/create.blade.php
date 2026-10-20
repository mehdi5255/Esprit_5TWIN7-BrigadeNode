@extends('layouts.front')
@section('title', 'Nouvelle demande de réparation')
@section('content')
@php $input = 'w-full rounded-xl border bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-forest-500 focus:ring-4 focus:ring-forest-100'; @endphp
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route('reparations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes réparations</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-700"><i data-lucide="wrench" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Demander une réparation</h1><p class="mt-2 text-slate-500">L'atelier choisi recevra votre demande et vous répondra rapidement.</p></div>
        @if($vetements->isEmpty())
            <div class="rounded-2xl bg-slate-50 p-6 text-center">
                <p class="font-semibold">Aucun vêtement disponible</p>
                <p class="mt-1 text-sm text-slate-500">Ajoutez d'abord un vêtement à votre dressing, ou attendez la fin de vos demandes en cours.</p>
                <div class="mt-4"><x-button :href="route('vetements.create')" label="Ajouter un vêtement" icon="plus" /></div>
            </div>
        @else
        <form method="POST" action="{{ route('reparations.store') }}" enctype="multipart/form-data" class="space-y-6" data-reparation data-pre-diagnostic="{{ route('reparations.pre-diagnostic') }}">
            @csrf
            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-slate-700">Vêtement à réparer</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($vetements as $vetement)
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 has-[:checked]:border-forest-500 has-[:checked]:bg-forest-50">
                        <input type="radio" name="vetement_id" value="{{ $vetement->id }}" required @checked(old('vetement_id', $selection) == $vetement->id) class="text-forest-600 focus:ring-forest-500">
                        @if($vetement->photoUrl())<img src="{{ $vetement->photoUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover">@endif
                        <span><span class="block text-sm font-semibold">{{ $vetement->nom }}</span><span class="text-xs text-slate-500">{{ $vetement->etat->label() }}</span></span>
                    </label>
                    @endforeach
                </div>
                @error('vetement_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
            </fieldset>
            {{-- Pré-diagnostic IA : la photo de l'abîmement remplit le type et la description. --}}
            <div class="rounded-2xl border border-violet-200 bg-violet-50/60 p-5">
                <p class="flex items-center gap-2 text-sm font-semibold text-violet-900"><i data-lucide="scan-search" class="h-4 w-4"></i>Photo du problème <span class="font-normal text-violet-700">(facultatif, recommandé)</span></p>
                <p class="mt-1 text-xs text-violet-800">Prenez la zone abîmée en gros plan : l’IA propose le type de réparation, une description et un prix indicatif. Vous vérifiez avant d’envoyer.</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <input type="file" name="photo_probleme" accept="image/*" data-photo-probleme class="block w-full max-w-xs text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-semibold file:text-violet-700">
                    <button type="button" data-analyser class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-60"><i data-lucide="sparkles" class="h-4 w-4"></i>Analyser la photo (IA)</button>
                </div>
                @error('photo_probleme')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
                <div data-resultat-ia class="mt-3 hidden text-sm"></div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Atelier</span>
                    <select name="atelier_id" required class="{{ $input }} {{ $errors->has('atelier_id') ? 'border-red-400' : 'border-slate-200' }}">
                        <option value="">Choisir un atelier…</option>
                        @foreach($ateliers as $atelier)<option value="{{ $atelier->id }}" @selected(old('atelier_id', $atelierChoisi) == $atelier->id)>{{ $atelier->nom }}{{ $atelier->ville ? ' — '.$atelier->ville : '' }}</option>@endforeach
                    </select>
                    @error('atelier_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Type de réparation</span>
                    <select name="type_reparation" required class="{{ $input }} border-slate-200">
                        @foreach(\App\Enums\TypeReparation::cases() as $type)<option value="{{ $type->value }}" @selected(old('type_reparation') === $type->value)>{{ $type->label() }}</option>@endforeach
                    </select>
                </label>
            </div>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Description du problème</span>
                <textarea name="description" rows="4" required placeholder="Ex. La couture de l'entrejambe est décousue sur 5 cm." class="{{ $input }} {{ $errors->has('description') ? 'border-red-400' : 'border-slate-200' }}">{{ old('description') }}</textarea>
                @error('description')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Budget maximum (€)</span>
                    <input type="number" name="budget_max" min="0" step="0.5" value="{{ old('budget_max') }}" placeholder="Ex. 25" class="{{ $input }} {{ $errors->has('budget_max') ? 'border-red-400' : 'border-slate-200' }}">
                    @error('budget_max')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Date souhaitée</span>
                    <input type="date" name="date_souhaitee" min="{{ now()->toDateString() }}" value="{{ old('date_souhaitee') }}" class="{{ $input }} {{ $errors->has('date_souhaitee') ? 'border-red-400' : 'border-slate-200' }}">
                    @error('date_souhaitee')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>
            <div class="flex justify-end gap-3">
                <x-button :href="route('reparations.index')" label="Annuler" variant="secondary" />
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Envoyer la demande</button>
            </div>
        </form>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Pré-diagnostic IA : envoie la photo, puis remplit le type et la description (le particulier corrige s'il le faut).
    const formReparation = document.querySelector('[data-reparation]');
    formReparation?.querySelector('[data-analyser]').addEventListener('click', async (e) => {
        const bouton = e.currentTarget;
        const fichier = formReparation.querySelector('[data-photo-probleme]').files[0];
        const zone = formReparation.querySelector('[data-resultat-ia]');
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        const euros = n => Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' €';
        zone.classList.remove('hidden');
        if (!fichier) { zone.innerHTML = '<p class="text-violet-900">Choisissez d’abord une photo de la zone abîmée.</p>'; return; }

        const corps = new FormData();
        corps.append('_token', formReparation.querySelector('[name=_token]').value);
        corps.append('photo', fichier);
        const vetement = formReparation.querySelector('input[name=vetement_id]:checked');
        if (vetement) corps.append('vetement_id', vetement.value);
        const atelier = formReparation.querySelector('[name=atelier_id]').value;
        if (atelier) corps.append('atelier_id', atelier);

        bouton.disabled = true;
        zone.innerHTML = '<p class="text-violet-800">Analyse de la photo…</p>';
        try {
            const reponse = await fetch(formReparation.dataset.preDiagnostic, { method: 'POST', body: corps, headers: { Accept: 'application/json' } });
            const r = await reponse.json();
            if (!reponse.ok) {
                const message = r.erreur ?? Object.values(r.errors ?? {})[0]?.[0] ?? (reponse.status === 429 ? 'Trop d’analyses : réessayez dans une minute.' : 'Analyse impossible.');
                zone.innerHTML = `<p class="text-amber-800">${esc(message)}</p>`;
                return;
            }
            formReparation.querySelector('[name=type_reparation]').value = r.type_reparation;
            formReparation.querySelector('[name=description]').value = r.description;
            const couleur = { 'légère': 'bg-emerald-100 text-emerald-800', moyenne: 'bg-amber-100 text-amber-800', importante: 'bg-rose-100 text-rose-800' }[r.gravite] ?? 'bg-slate-100';
            zone.innerHTML = `<div class="rounded-xl bg-white p-4">
                <p class="flex flex-wrap items-center gap-2"><span class="font-semibold">${esc(r.type_libelle)}</span><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ${couleur}">Gravité ${esc(r.gravite)}</span></p>
                ${r.fourchette
                    ? `<p class="mt-2">Prix indicatif : <span class="font-semibold">${euros(r.fourchette.min)} – ${euros(r.fourchette.max)}</span> <span class="text-xs text-slate-500">(d’après ${r.fourchette.nb} devis acceptés comparables)</span></p>`
                    : '<p class="mt-2 text-slate-500">Pas encore assez de devis comparables pour un prix indicatif.</p>'}
                ${r.conseil ? `<p class="mt-2 text-slate-600"><span class="font-semibold">Conseil :</span> ${esc(r.conseil)}</p>` : ''}
                <p class="mt-2 text-xs text-violet-700">Le type et la description ont été remplis ci-dessous : vérifiez-les et corrigez si besoin. L’atelier établira le vrai devis après avoir examiné le vêtement.</p>
            </div>`;
        } catch {
            zone.innerHTML = '<p class="text-amber-800">L’analyse automatique est indisponible : décrivez le problème vous-même.</p>';
        } finally {
            bouton.disabled = false;
        }
    });
</script>
@endpush
