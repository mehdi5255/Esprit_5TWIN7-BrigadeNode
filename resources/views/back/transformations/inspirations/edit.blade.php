@extends('layouts.back')
@section('title', 'Modifier ' . $inspiration->titre)
@section('breadcrumb', 'Atelier / Inspirations / Modifier')
@section('content')
<form method="POST" action="{{ route('atelier.inspirations.update', $inspiration) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('back.transformations.inspirations._form', ['submit' => 'Enregistrer'])
</form>
@endsection
