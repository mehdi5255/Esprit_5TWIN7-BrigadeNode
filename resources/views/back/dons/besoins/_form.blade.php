@csrf
<section class="max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <x-champ label="Titre" name="titre" :value="$besoin->titre" :required="true" placeholder="Ex. Vêtements chauds adultes" />
    <div class="grid gap-5 sm:grid-cols-3">
        <x-champ label="Type de vêtement" name="categorie_id" type="select" :value="$besoin->categorie_id" vide="Tous vêtements" :options="$categories->pluck('libelle', 'id')" />
        <x-champ label="Quantité" name="quantite" type="number" :value="$besoin->quantite" :required="true" min="1" />
        <x-champ label="Priorité" name="priorite" type="select" :value="$besoin->priorite ?? 'moyenne'" :required="true" :options="collect(\App\Enums\PrioriteBesoin::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])" />
    </div>
    <x-champ label="Description" name="description" type="textarea" :value="$besoin->description" placeholder="Tailles, saisons, état attendu…" />
    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="actif" value="1" @checked(old('actif', $besoin->actif ?? true)) class="rounded border-slate-300 text-forest-600 focus:ring-forest-500">Besoin actif (visible des donateurs)</label>
    <div class="flex gap-3">
        <x-button :href="route('association.besoins.index')" label="Annuler" variant="secondary" />
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>{{ $submit }}</button>
    </div>
</section>
