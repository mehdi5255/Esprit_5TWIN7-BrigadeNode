{{--
    Composition du vêtement (N-N avec pourcentage) : recopier l'étiquette, la scanner (OCR dans le navigateur),
    ou cocher « Je n'ai plus l'étiquette » pour une composition typique de la catégorie.
    Attend $vetement, $matieres (catalogue) et $typiques (catégorie => poids + composition).
--}}
@php
    $input = 'w-full rounded-xl border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-forest-500 focus:ring-4 focus:ring-forest-100';
    $lignes = old('composition') !== null
        ? collect(old('composition'))->values()
        : $vetement->matieres->map(fn ($m) => ['matiere_id' => $m->id, 'pourcentage' => $m->pivot->pourcentage]);
    if ($lignes->isEmpty()) {
        $lignes = collect([['matiere_id' => '', 'pourcentage' => '']]);
    }
    $catalogue = $matieres->map(fn ($m) => ['id' => $m->id, 'nom' => $m->nom, 'mots' => $m->motsCles()])->values();
@endphp
<fieldset class="space-y-4 rounded-2xl border border-slate-200 p-5" data-composition data-catalogue='@json($catalogue)' data-typiques='@json($typiques)'>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <legend class="text-sm font-semibold text-slate-700">Composition</legend>
            <p class="mt-1 text-xs text-slate-500">Recopiez l'étiquette cousue à l'intérieur (col, couture de côté ou taille). Elle sert à estimer l'impact évité et à vous donner des conseils d'entretien.</p>
        </div>
        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700 hover:bg-violet-100">
            <i data-lucide="scan-text" class="h-4 w-4"></i><span data-scan-libelle>Scanner l'étiquette</span>
            <input type="file" accept="image/*" capture="environment" class="sr-only" data-scan>
        </label>
    </div>
    <p class="text-xs text-slate-400">Pas d'étiquette sous la main ? <a href="{{ asset('demo/etiquette-composition.png') }}" target="_blank" class="font-semibold text-violet-700 hover:underline">Essayez le scan avec cet exemple</a> (enregistrez l'image, puis scannez-la).</p>
    <p data-scan-message class="hidden rounded-xl px-3 py-2 text-xs"></p>

    <div class="space-y-2" data-lignes>
        @foreach($lignes as $i => $ligne)
        <div class="flex items-start gap-2" data-ligne>
            <select name="composition[{{ $i }}][matiere_id]" class="{{ $input }} flex-1 {{ $errors->has("composition.$i.matiere_id") ? 'border-red-400' : 'border-slate-200' }}">
                <option value="">Matière…</option>
                @foreach($matieres as $matiere)<option value="{{ $matiere->id }}" @selected((string) ($ligne['matiere_id'] ?? '') === (string) $matiere->id)>{{ $matiere->nom }}{{ $matiere->abreviations ? ' — '.implode(', ', array_slice($matiere->abreviations, 0, 3)) : '' }}</option>@endforeach
            </select>
            <div class="relative w-28"><input type="number" name="composition[{{ $i }}][pourcentage]" value="{{ $ligne['pourcentage'] ?? '' }}" min="1" max="100" placeholder="%" class="{{ $input }} pr-8 {{ $errors->has("composition.$i.pourcentage") ? 'border-red-400' : 'border-slate-200' }}"><span class="pointer-events-none absolute right-3 top-2.5 text-sm text-slate-400">%</span></div>
            <button type="button" data-retirer class="rounded-xl p-2.5 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Retirer cette matière"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
        @endforeach
    </div>
    @foreach($errors->get('composition.*') as $messages)<span class="block text-xs text-red-600">{{ $messages[0] }}</span>@endforeach
    @error('composition')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror

    <div class="flex flex-wrap items-center justify-between gap-3">
        <button type="button" data-ajouter class="inline-flex items-center gap-1 text-sm font-semibold text-forest-700 hover:underline"><i data-lucide="plus" class="h-4 w-4"></i>Ajouter une matière</button>
        <p class="text-sm font-semibold" data-total></p>
    </div>

    <div class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
        <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Poids (g)</span>
            <input type="number" name="poids_grammes" value="{{ old('poids_grammes', $vetement->poids_grammes) }}" min="10" max="20000" placeholder="Ex. 600" class="{{ $input }} {{ $errors->has('poids_grammes') ? 'border-red-400' : 'border-slate-200' }}">
            @error('poids_grammes')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@else<span class="mt-1 block text-xs text-slate-400">Une pesée de cuisine suffit. T-shirt ≈ 150 g, jean ≈ 600 g, manteau ≈ 1,2 kg.</span>@enderror
        </label>
        <label class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 text-sm">
            <input type="hidden" name="composition_estimee" value="0">
            <input type="checkbox" name="composition_estimee" value="1" data-estimee @checked(old('composition_estimee', $vetement->composition_estimee)) class="mt-0.5 rounded border-slate-300 text-forest-600 focus:ring-forest-500">
            <span><span class="block font-semibold">Je n'ai plus l'étiquette</span><span class="text-xs text-slate-500">On propose une composition typique de la catégorie, marquée « estimée ». Vous pouvez la corriger.</span></span>
        </label>
    </div>

    <template data-modele>
        <div class="flex items-start gap-2" data-ligne>
            <select name="composition[__i__][matiere_id]" class="{{ $input }} flex-1 border-slate-200">
                <option value="">Matière…</option>
                @foreach($matieres as $matiere)<option value="{{ $matiere->id }}">{{ $matiere->nom }}{{ $matiere->abreviations ? ' — '.implode(', ', array_slice($matiere->abreviations, 0, 3)) : '' }}</option>@endforeach
            </select>
            <div class="relative w-28"><input type="number" name="composition[__i__][pourcentage]" min="1" max="100" placeholder="%" class="{{ $input }} pr-8 border-slate-200"><span class="pointer-events-none absolute right-3 top-2.5 text-sm text-slate-400">%</span></div>
            <button type="button" data-retirer class="rounded-xl p-2.5 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Retirer cette matière"><i data-lucide="x" class="h-4 w-4"></i></button>
        </div>
    </template>
</fieldset>

@push('scripts')
<script src="{{ asset('front/js/composition.js') }}"></script>
@endpush
