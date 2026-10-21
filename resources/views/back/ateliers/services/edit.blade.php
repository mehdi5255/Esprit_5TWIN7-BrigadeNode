@extends('layouts.back')
@section('title', 'Modifier ' . $service->nom)
@section('breadcrumb', 'Atelier / Services / Modifier')
@section('content')
<form method="POST" action="{{ route('atelier.services.update', $service) }}">
    @method('PUT')
    @include('back.ateliers.services._form', ['submit' => 'Enregistrer'])
</form>
@endsection
