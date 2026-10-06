@extends('layouts.front')

@section('title', 'Modifier ' . $vetement->nom)

@section('content')
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route('vetements.show', $vetement) }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour au vêtement</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-forest-100 text-forest-700"><i data-lucide="pencil" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Modifier le vêtement</h1><p class="mt-2 text-slate-500">Mettez à jour les informations de « {{ $vetement->nom }} ».</p></div>
        <form method="POST" action="{{ route('vetements.update', $vetement) }}" enctype="multipart/form-data" class="space-y-6">
            @method('PUT')
            @include('front.vetements._form', ['submit' => 'Enregistrer'])
        </form>
    </div>
</section>
@endsection
