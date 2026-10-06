@extends('layouts.back')
@section('title', 'Modifier la catégorie')
@section('breadcrumb', 'Catégories / Modifier')
@section('content')
<section class="max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('admin.categories.update', $categorie) }}" class="space-y-4">
        @method('PUT')
        @include('back.vetements.categories._form', ['submit' => 'Enregistrer'])
    </form>
</section>
@endsection
