@extends('layouts.front')
@section('title', 'Modifier le don ' . $demande->reference())
@section('content')
@component('partials.front.edition-demande', [
    'demande' => $demande,
    'segment' => 'dons', 'titre' => 'Modifier ma proposition de don', 'icone' => 'heart-handshake', 'couleur' => 'bg-rose-50 text-rose-600',
    'structure' => 'Association : ' . $demande->association->nom,
])
    @slot('champs')
        <x-champ label="Besoin concerné" name="besoin_id" type="select" :value="$demande->besoin_id" vide="Don libre (pas de besoin précis)" :options="$besoins->mapWithKeys(fn ($b) => [$b->id => $b->titre . ' — ' . $b->priorite->label()])" />
        <x-champ label="Message à l’association" name="message" type="textarea" :value="$demande->message" />
    @endslot
@endcomponent
@endsection
