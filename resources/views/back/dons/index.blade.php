@extends('layouts.back')
@section('title', $espace === 'admin' ? 'Demandes de don' : 'Dons reçus')
@section('breadcrumb', $espace === 'admin' ? 'Supervision / Dons' : 'Dons / Propositions reçues')
@section('content')
@include('partials.back.demandes.liste', [
    'type' => 'dons',
    'statuts' => \App\Enums\StatutDon::cases(),
    'cartes' => [
        ['Propositions en attente', ['en_attente'], 'inbox', 'blue'],
        ['À réceptionner', ['acceptee'], 'package', 'amber'],
        ['En préparation', ['depot_remis', 'en_traitement'], 'hand-heart', 'violet'],
        ['Remis aux bénéficiaires', ['remis_beneficiaire'], 'heart', 'green'],
    ],
])
@endsection
