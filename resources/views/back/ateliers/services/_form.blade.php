@csrf
<section class="max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-champ label="Type" name="type" type="select" :required="true" :value="$service->type" :options="collect(\App\Enums\TypeService::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])" />
        <x-champ label="Nom du service" name="nom" :value="$service->nom" :required="true" placeholder="Ex. Ourlet de pantalon" />
    </div>
    <x-champ label="Description" name="description" type="textarea" :value="$service->description" />
    <div class="grid gap-5 sm:grid-cols-3">
        <x-champ label="Prix (€)" name="prix" type="number" :value="$service->prix" min="0" step="0.5" help="Vide = sur devis." />
        <x-champ label="Durée (min)" name="duree" type="number" :value="$service->duree" min="1" />
        <x-champ label="Délai (jours)" name="delai_jours" type="number" :value="$service->delai_jours" min="0" />
    </div>
    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="actif" value="1" @checked(old('actif', $service->actif ?? true)) class="rounded border-slate-300 text-forest-600 focus:ring-forest-500">Service actif (visible des particuliers)</label>
    <div class="flex gap-3">
        <x-button :href="route('atelier.services.index')" label="Annuler" variant="secondary" />
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>{{ $submit }}</button>
    </div>
</section>
