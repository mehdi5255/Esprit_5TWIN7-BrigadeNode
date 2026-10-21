@extends('layouts.back')
@section('title', 'Demandes de transformation')
@section('breadcrumb', $espace === 'admin' ? 'Supervision / Transformations' : 'Demandes / Transformations')
@section('content')
@include('partials.back.demandes.liste', [
    'type' => 'transformations',
    'statuts' => \App\Enums\StatutDemande::cases(),
    'cartes' => [
        ['Nouveaux projets', ['en_attente'], 'inbox', 'blue'],
        ['À déposer', ['acceptee'], 'package', 'amber'],
        ['En confection', ['depot_remis', 'en_traitement'], 'scissors', 'violet'],
        ['Prêts à récupérer', ['pret'], 'package-check', 'green'],
    ],
])
@endsection
