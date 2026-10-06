@extends('layouts.front')
@section('title', 'Modifier le projet ' . $demande->reference())
@section('content')
@component('partials.front.edition-demande', [
    'demande' => $demande,
    'segment' => 'transformations', 'titre' => 'Modifier mon projet de transformation', 'icone' => 'scissors', 'couleur' => 'bg-violet-50 text-violet-700',
    'structure' => 'Atelier : ' . $demande->atelier->nom,
])
    @slot('champs')
        <x-champ label="Votre idée de transformation" name="idee" type="textarea" :rows="4" :required="true" :value="$demande->idee" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ label="Budget maximum (€)" name="budget_max" type="number" :value="$demande->budget_max" min="0" step="0.5" />
            <x-champ label="Date souhaitée" name="date_souhaitee" type="date" :value="$demande->date_souhaitee?->toDateString()" :min="now()->toDateString()" />
        </div>
    @endslot
@endcomponent
@endsection
