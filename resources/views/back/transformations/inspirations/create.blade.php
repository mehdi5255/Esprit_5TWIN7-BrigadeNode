@extends('layouts.back')
@section('title', 'Nouvelle inspiration')
@section('breadcrumb', 'Atelier / Inspirations / Ajouter')
@section('content')
<form method="POST" action="{{ route('atelier.inspirations.store') }}" enctype="multipart/form-data">
    @include('back.transformations.inspirations._form', ['submit' => 'Publier l’inspiration'])
</form>
@endsection
