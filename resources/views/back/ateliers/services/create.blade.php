@extends('layouts.back')
@section('title', 'Nouveau service')
@section('breadcrumb', 'Atelier / Services / Ajouter')
@section('content')
<form method="POST" action="{{ route('atelier.services.store') }}">
    @include('back.ateliers.services._form', ['submit' => 'Créer le service'])
</form>
@endsection
