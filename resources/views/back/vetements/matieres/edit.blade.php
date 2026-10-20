@extends('layouts.back')
@section('title', 'Modifier ' . $matiere->nom)
@section('breadcrumb', 'Administration / Matières / Modifier')
@section('content')
@if($matiere->vetements_count)
<p class="mb-5 max-w-2xl rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">Cette matière entre dans la composition de {{ $matiere->vetements_count }} vêtement(s) : modifier ses facteurs change leur impact affiché.</p>
@endif
<form method="POST" action="{{ route('admin.matieres.update', $matiere) }}">
    @method('PUT')
    @include('back.vetements.matieres._form', ['submit' => 'Enregistrer'])
</form>
@endsection
