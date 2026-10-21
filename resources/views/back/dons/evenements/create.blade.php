@extends('layouts.back')
@section('title', 'Nouvel événement')
@section('breadcrumb', 'Association / Événements / Ajouter')
@section('content')
<form method="POST" action="{{ route('association.evenements.store') }}">
    @include('back.dons.evenements._form', ['submit' => 'Publier l’événement'])
</form>
@endsection
