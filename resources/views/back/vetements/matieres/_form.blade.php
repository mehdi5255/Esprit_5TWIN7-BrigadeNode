{{-- Formulaire de matière (création / modification). Attend $matiere et $submit. --}}
@csrf
<section class="max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-champ label="Nom" name="nom" :value="$matiere->nom" :required="true" placeholder="Ex. Coton" />
        <x-champ label="Type" name="type" type="select" :required="true" :value="$matiere->type" :options="collect(\App\Enums\TypeMatiere::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])" />
    </div>
    <x-champ label="Abréviations et autres noms" name="abreviations" :value="implode(', ', $matiere->abreviations ?? [])" placeholder="Ex. CO, cotton, algodón" help="Séparés par des virgules. Ils servent à reconnaître la matière sur les étiquettes (saisie et scan)." />
    <div class="grid gap-5 sm:grid-cols-2">
        <x-champ label="CO₂ émis (kg par kg fabriqué)" name="co2_kg_par_kg" type="number" :value="$matiere->co2_kg_par_kg" :required="true" min="0" step="0.1" />
        <x-champ label="Eau consommée (litres par kg)" name="eau_litres_par_kg" type="number" :value="$matiere->eau_litres_par_kg" :required="true" min="0" />
    </div>
    <p class="text-xs text-slate-400">Ordres de grandeur de fabrication : ils servent à estimer l'impact évité quand un vêtement est réparé, transformé ou donné.</p>
    <x-champ label="Conseils d'entretien" name="entretien" type="textarea" :value="$matiere->entretien" placeholder="Ex. Lavage 30 °C, séchage à plat…" help="Affichés sur la fiche des vêtements qui contiennent cette matière." />
    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="actif" value="1" @checked(old('actif', $matiere->actif ?? true)) class="rounded border-slate-300 text-forest-600 focus:ring-forest-500">Proposée aux particuliers</label>
    <div class="flex gap-3">
        <x-button :href="route('admin.matieres.index')" label="Annuler" variant="secondary" />
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>{{ $submit }}</button>
    </div>
</section>
