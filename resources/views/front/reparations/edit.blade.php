@extends('layouts.front')
@section('title', 'Modifier la demande ' . $demande->reference())
@section('content')
@component('partials.front.edition-demande', [
    'demande' => $demande,
    'segment' => 'reparations', 'titre' => 'Modifier ma demande de réparation', 'icone' => 'wrench', 'couleur' => 'bg-amber-50 text-amber-700',
    'structure' => 'Atelier : ' . $demande->atelier->nom,
])
    @slot('champs')
        <x-champ label="Type de réparation" name="type_reparation" type="select" :required="true" :value="$demande->type_reparation" :options="collect(\App\Enums\TypeReparation::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])" />
        <x-champ label="Description du problème" name="description" type="textarea" :rows="4" :required="true" :value="$demande->description" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ label="Budget maximum (€)" name="budget_max" type="number" :value="$demande->budget_max" min="0" step="0.5" />
            <x-champ label="Date souhaitée" name="date_souhaitee" type="date" :value="$demande->date_souhaitee?->toDateString()" :min="now()->toDateString()" />
        </div>
    @endslot
@endcomponent
@endsection
