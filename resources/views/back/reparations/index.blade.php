@extends('layouts.back')
@section('title', 'Demandes de réparation')
@section('breadcrumb', $espace === 'admin' ? 'Supervision / Réparations' : 'Demandes / Réparations')
@section('content')
@include('partials.back.demandes.liste', [
    'type' => 'reparations',
    'statuts' => \App\Enums\StatutDemande::cases(),
    'cartes' => [
        ['En attente', ['en_attente'], 'inbox', 'blue'],
        ['À déposer', ['acceptee'], 'package', 'amber'],
        ['En atelier', ['depot_remis', 'en_traitement'], 'wrench', 'violet'],
        ['Prêtes à récupérer', ['pret'], 'package-check', 'green'],
    ],
])
@endsection
