{{-- Formulaire d'inspiration (création / modification). Attend $inspiration, $categories, $submit. --}}
@csrf
<section class="max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-champ label="Titre" name="titre" :value="$inspiration->titre" :required="true" placeholder="Ex. Jean → Sac cabas" />
        <x-champ label="Vêtement de départ" name="categorie_id" type="select" :required="true" vide="Choisir une catégorie…" :value="$inspiration->categorie_id" :options="$categories->pluck('libelle', 'id')" help="Les particuliers verront ce modèle pour leurs vêtements de cette catégorie." />
    </div>
    <x-champ label="Description" name="description" type="textarea" :rows="4" :value="$inspiration->description" :required="true" placeholder="Ce que vous réalisez, les matières, les finitions…" />
    <div class="grid gap-5 sm:grid-cols-2">
        <x-champ label="Prix indicatif (€)" name="prix_indicatif" type="number" :value="$inspiration->prix_indicatif" min="0" step="0.5" help="Vide = sur devis." />
        <x-champ label="Délai habituel (jours)" name="delai_jours" type="number" :value="$inspiration->delai_jours" min="1" />
    </div>
    @if($inspiration->photoUrl())
    <div class="flex items-center gap-4"><img src="{{ $inspiration->photoUrl() }}" alt="" class="h-20 w-20 rounded-xl object-cover"><p class="text-sm text-slate-500">Photo actuelle. Envoyez-en une nouvelle pour la remplacer.</p></div>
    @endif
    <x-champ label="Photo du résultat" name="photo" type="file" accept="image/*" data-max-ko="5120" help="JPG ou PNG, 5 Mo maximum." />
    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="actif" value="1" @checked(old('actif', $inspiration->actif ?? true)) class="rounded border-slate-300 text-forest-600 focus:ring-forest-500">Visible dans la galerie</label>
    <div class="flex gap-3">
        <x-button :href="$inspiration->exists ? route('atelier.inspirations.show', $inspiration) : route('atelier.inspirations.index')" label="Annuler" variant="secondary" />
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>{{ $submit }}</button>
    </div>
</section>
