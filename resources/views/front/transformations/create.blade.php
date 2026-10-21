@extends('layouts.front')
@section('title', 'Nouveau projet de transformation')
@section('content')
@php
    $inspirationCochee = (string) old('inspiration_id', $inspirationChoisie?->id);
    $sansVetementCompatible = $inspirationChoisie && ! $vetements->contains('categorie_id', $inspirationChoisie->categorie_id);
@endphp
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route('transformations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes transformations</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-violet-50 text-violet-700"><i data-lucide="scissors" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Transformer un vêtement</h1><p class="mt-2 text-slate-500">Choisissez un modèle proposé par un atelier, ou proposez votre propre idée. <a href="{{ route('inspirations.index') }}" class="font-semibold text-violet-700 hover:underline">Voir toutes les inspirations</a></p>
            <a href="{{ route('transformations.idees') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-700 hover:bg-violet-100"><i data-lucide="wand-sparkles" class="h-4 w-4"></i>Pas d’idée ? Demandez à l’IA</a></div>
        @if($vetements->isEmpty())
            @include('partials.front.aucun-vetement')
        @elseif($ateliers->isEmpty())
            <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Aucun atelier ne propose de transformation pour le moment.</p>
        @else
        <form method="POST" action="{{ route('transformations.store') }}" class="space-y-6" data-transformation>
            @csrf
            @if($sansVetementCompatible)
            <p class="flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900"><i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0"></i>« {{ $inspirationChoisie->titre }} » concerne la catégorie « {{ $inspirationChoisie->categorie->libelle }} », mais vous n'avez aucun vêtement disponible de cette catégorie. <a href="{{ route('vetements.create') }}" class="font-semibold underline">Ajouter un vêtement</a></p>
            @endif
            @include('partials.front.choix-vetement')

            @if($apercu = old('apercu_ia', $apercu))
            <div class="flex items-center gap-4 rounded-2xl border border-violet-200 bg-violet-50 p-4">
                <img src="{{ \App\Support\Photo::url($apercu) }}" alt="Aperçu généré par l’IA" class="h-20 w-20 shrink-0 rounded-xl object-cover">
                <p class="text-sm text-violet-900"><span class="font-semibold">Aperçu IA joint au projet.</span> L’atelier le recevra comme référence du résultat souhaité.</p>
                <input type="hidden" name="apercu_ia" value="{{ $apercu }}">
            </div>
            @endif
            @error('apercu_ia')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror

            {{-- Point de départ : une inspiration de la même catégorie que le vêtement, ou une idée libre. --}}
            <fieldset>
                <legend class="mb-1 text-sm font-semibold text-slate-700">Point de départ</legend>
                <p class="mb-3 text-xs text-slate-500">Les inspirations affichées correspondent à la catégorie du vêtement choisi.</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($inspirations as $inspiration)
                    <label data-inspiration data-categorie="{{ $inspiration->categorie_id }}" class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50">
                        <input type="radio" name="inspiration_id" value="{{ $inspiration->id }}" data-atelier-nom="{{ $inspiration->atelier->nom }}" data-idee="{{ $inspiration->ideePreremplie() }}" @checked($inspirationCochee === (string) $inspiration->id) class="text-violet-600 focus:ring-violet-500">
                        @if($inspiration->photoUrl())<img src="{{ $inspiration->photoUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover">
                        @else<span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600"><i data-lucide="sparkles" class="h-5 w-5"></i></span>@endif
                        <span class="min-w-0"><span class="block text-sm font-semibold">{{ $inspiration->titre }}</span><span class="text-xs text-slate-500">{{ $inspiration->atelier->nom }} · {{ $inspiration->prixFormate() }}</span></span>
                    </label>
                    @endforeach
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-dashed border-slate-300 p-3 has-[:checked]:border-forest-500 has-[:checked]:bg-forest-50">
                        <input type="radio" name="inspiration_id" value="" @checked($inspirationCochee === '') class="text-forest-600 focus:ring-forest-500">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-500"><i data-lucide="lightbulb" class="h-5 w-5"></i></span>
                        <span><span class="block text-sm font-semibold">J'ai ma propre idée</span><span class="text-xs text-slate-500">Je choisis l'atelier et je décris mon projet</span></span>
                    </label>
                </div>
                <p data-aucune class="mt-3 hidden text-sm text-slate-500">Aucune inspiration pour ce type de vêtement : décrivez votre propre idée.</p>
                @error('inspiration_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
            </fieldset>

            <div data-atelier-libre>
                <x-champ label="Atelier d’upcycling" name="atelier_id" type="select" :required="true" vide="Choisir un atelier…" :value="$atelierChoisi" :options="$ateliers->mapWithKeys(fn ($a) => [$a->id => $a->nom.($a->specialite ? ' — '.$a->specialite : '').($a->ville ? ' ('.$a->ville.')' : '')])" />
            </div>
            <p data-atelier-impose class="hidden rounded-xl bg-violet-50 px-4 py-3 text-sm text-violet-900"><i data-lucide="store" class="mr-1 inline h-4 w-4"></i>Atelier : <span data-atelier-nom class="font-semibold"></span> (celui qui propose ce modèle)</p>
            <x-champ label="Votre idée de transformation" name="idee" type="textarea" :rows="4" :required="true" :value="$ideeIa" placeholder="Ex. Transformer cette robe longue en jupe mi-longue avec une poche plaquée." help="Avec une inspiration, l'idée est préremplie : complétez-la si besoin (couleur, longueur…)." />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ label="Budget maximum (€)" name="budget_max" type="number" placeholder="Ex. 60" min="0" step="0.5" />
                <x-champ label="Date souhaitée" name="date_souhaitee" type="date" :min="now()->toDateString()" />
            </div>
            <div class="flex justify-end gap-3">
                <x-button :href="route('transformations.index')" label="Annuler" variant="secondary" />
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Envoyer le projet</button>
            </div>
        </form>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Suggestions : n'affiche que les inspirations de la catégorie du vêtement choisi.
    // Avec une inspiration, l'atelier est imposé par le modèle et l'idée est préremplie.
    const formTransformation = document.querySelector('[data-transformation]');
    if (formTransformation) {
        const cartes = formTransformation.querySelectorAll('[data-inspiration]');
        const ideeLibre = formTransformation.querySelector('input[name=inspiration_id][value=""]');
        const aucune = formTransformation.querySelector('[data-aucune]');
        const blocAtelier = formTransformation.querySelector('[data-atelier-libre]');
        const selectAtelier = formTransformation.querySelector('[name=atelier_id]');
        const atelierImpose = formTransformation.querySelector('[data-atelier-impose]');
        const idee = formTransformation.querySelector('[name=idee]');
        let ideeAuto = '';

        const majAtelier = () => {
            const choix = formTransformation.querySelector('input[name=inspiration_id]:checked');
            const avecModele = !!(choix && choix.value);
            blocAtelier.classList.toggle('hidden', avecModele);
            atelierImpose.classList.toggle('hidden', !avecModele);
            selectAtelier.disabled = avecModele;
            if (avecModele) {
                atelierImpose.querySelector('[data-atelier-nom]').textContent = choix.dataset.atelierNom;
                if (!idee.value.trim() || idee.value === ideeAuto) { idee.value = choix.dataset.idee; ideeAuto = idee.value; }
            } else if (idee.value === ideeAuto) {
                idee.value = ''; ideeAuto = '';
            }
        };

        const majInspirations = () => {
            const categorie = formTransformation.querySelector('input[name=vetement_id]:checked')?.dataset.categorie;
            let visibles = 0;
            cartes.forEach(carte => {
                const ok = !categorie || carte.dataset.categorie === categorie;
                carte.classList.toggle('hidden', !ok);
                if (ok) visibles++;
                if (!ok && carte.querySelector('input').checked) ideeLibre.checked = true;
            });
            aucune.classList.toggle('hidden', visibles > 0);
            majAtelier();
        };

        formTransformation.querySelectorAll('input[name=vetement_id]').forEach(r => r.addEventListener('change', majInspirations));
        formTransformation.querySelectorAll('input[name=inspiration_id]').forEach(r => r.addEventListener('change', majAtelier));
        majInspirations();
    }
</script>
@endpush
