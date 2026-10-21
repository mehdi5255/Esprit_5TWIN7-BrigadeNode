@extends('layouts.back')
@section('title', 'Modifier le besoin')
@section('breadcrumb', 'Association / Besoins / Modifier')
@section('content')
<form method="POST" action="{{ route('association.besoins.update', $besoin) }}">
    @method('PUT')
    @include('back.dons.besoins._form', ['submit' => 'Enregistrer'])
</form>
@endsection
