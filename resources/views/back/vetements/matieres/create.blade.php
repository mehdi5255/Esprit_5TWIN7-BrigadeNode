@extends('layouts.back')
@section('title', 'Nouvelle matière')
@section('breadcrumb', 'Administration / Matières / Ajouter')
@section('content')
<form method="POST" action="{{ route('admin.matieres.store') }}">
    @include('back.vetements.matieres._form', ['submit' => 'Ajouter la matière'])
</form>
@endsection
