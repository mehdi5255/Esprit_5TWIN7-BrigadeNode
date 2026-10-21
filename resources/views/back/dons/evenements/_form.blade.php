{{-- Formulaire d'événement (création / modification). Attend $evenement et $submit. --}}
@csrf
<section class="max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-champ label="Type" name="type" type="select" :required="true" :value="$evenement->type" :options="collect(\App\Enums\TypeEvenement::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])" help="Collecte : les particuliers y déposent leurs dons. Distribution : vous y remettez les dons." />
        <x-champ label="Titre" name="titre" :value="$evenement->titre" :required="true" placeholder="Ex. Grande collecte d'hiver" />
    </div>
    <x-champ label="Description" name="description" type="textarea" :value="$evenement->description" placeholder="Ce qu'on peut apporter, comment s'organiser…" />
    @include('back.dons.evenements._lieu')
    <div class="grid gap-5 sm:grid-cols-2">
        <x-champ label="Début" name="debut" type="datetime-local" :value="$evenement->debut?->format('Y-m-d\TH:i')" :required="true" />
        <x-champ label="Fin" name="fin" type="datetime-local" :value="$evenement->fin?->format('Y-m-d\TH:i')" :required="true" />
    </div>
    <x-champ label="Nombre de places" name="places" type="number" :value="$evenement->places" min="1" help="Collectes uniquement : nombre de dons acceptés. Vide = sans limite." />
    <div class="flex gap-3">
        <x-button :href="$evenement->exists ? route('association.evenements.show', $evenement) : route('association.evenements.index')" label="Annuler" variant="secondary" />
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>{{ $submit }}</button>
    </div>
</section>
