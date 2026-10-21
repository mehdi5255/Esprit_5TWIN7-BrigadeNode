@extends('layouts.back')
@section('title', 'Modifier ' . $evenement->titre)
@section('breadcrumb', 'Association / Événements / Modifier')
@section('content')
<form method="POST" action="{{ route('association.evenements.update', $evenement) }}">
    @method('PUT')
    @include('back.dons.evenements._form', ['submit' => 'Enregistrer'])
</form>
@endsection
