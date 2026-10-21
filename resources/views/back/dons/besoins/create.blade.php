@extends('layouts.back')
@section('title', 'Déclarer un besoin')
@section('breadcrumb', 'Association / Besoins / Ajouter')
@section('content')
<form method="POST" action="{{ route('association.besoins.store') }}">
    @include('back.dons.besoins._form', ['submit' => 'Publier le besoin'])
</form>
@endsection
