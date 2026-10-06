@extends('layouts.front')
@section('title', 'Nouvelle demande de réparation')
@section('content')
@php $input = 'w-full rounded-xl border bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-forest-500 focus:ring-4 focus:ring-forest-100'; @endphp
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route('reparations.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes réparations</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-700"><i data-lucide="wrench" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Demander une réparation</h1><p class="mt-2 text-slate-500">L'atelier choisi recevra votre demande et vous répondra rapidement.</p></div>
        @if($vetements->isEmpty())
            <div class="rounded-2xl bg-slate-50 p-6 text-center">
                <p class="font-semibold">Aucun vêtement disponible</p>
                <p class="mt-1 text-sm text-slate-500">Ajoutez d'abord un vêtement à votre dressing, ou attendez la fin de vos demandes en cours.</p>
                <div class="mt-4"><x-button :href="route('vetements.create')" label="Ajouter un vêtement" icon="plus" /></div>
            </div>
        @else
        <form method="POST" action="{{ route('reparations.store') }}" class="space-y-6">
            @csrf
            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-slate-700">Vêtement à réparer</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($vetements as $vetement)
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 has-[:checked]:border-forest-500 has-[:checked]:bg-forest-50">
                        <input type="radio" name="vetement_id" value="{{ $vetement->id }}" required @checked(old('vetement_id', $selection) == $vetement->id) class="text-forest-600 focus:ring-forest-500">
                        @if($vetement->photoUrl())<img src="{{ $vetement->photoUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover">@endif
                        <span><span class="block text-sm font-semibold">{{ $vetement->nom }}</span><span class="text-xs text-slate-500">{{ $vetement->etat->label() }}</span></span>
                    </label>
                    @endforeach
                </div>
                @error('vetement_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
            </fieldset>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Atelier</span>
                    <select name="atelier_id" required class="{{ $input }} {{ $errors->has('atelier_id') ? 'border-red-400' : 'border-slate-200' }}">
                        <option value="">Choisir un atelier…</option>
                        @foreach($ateliers as $atelier)<option value="{{ $atelier->id }}" @selected(old('atelier_id', $atelierChoisi) == $atelier->id)>{{ $atelier->nom }}{{ $atelier->ville ? ' — '.$atelier->ville : '' }}</option>@endforeach
                    </select>
                    @error('atelier_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Type de réparation</span>
                    <select name="type_reparation" required class="{{ $input }} border-slate-200">
                        @foreach(\App\Enums\TypeReparation::cases() as $type)<option value="{{ $type->value }}" @selected(old('type_reparation') === $type->value)>{{ $type->label() }}</option>@endforeach
                    </select>
                </label>
            </div>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Description du problème</span>
                <textarea name="description" rows="4" required placeholder="Ex. La couture de l'entrejambe est décousue sur 5 cm." class="{{ $input }} {{ $errors->has('description') ? 'border-red-400' : 'border-slate-200' }}">{{ old('description') }}</textarea>
                @error('description')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Budget maximum (€)</span>
                    <input type="number" name="budget_max" min="0" step="0.5" value="{{ old('budget_max') }}" placeholder="Ex. 25" class="{{ $input }} {{ $errors->has('budget_max') ? 'border-red-400' : 'border-slate-200' }}">
                    @error('budget_max')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Date souhaitée</span>
                    <input type="date" name="date_souhaitee" min="{{ now()->toDateString() }}" value="{{ old('date_souhaitee') }}" class="{{ $input }} {{ $errors->has('date_souhaitee') ? 'border-red-400' : 'border-slate-200' }}">
                    @error('date_souhaitee')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>
            <div class="flex justify-end gap-3">
                <x-button :href="route('reparations.index')" label="Annuler" variant="secondary" />
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Envoyer la demande</button>
            </div>
        </form>
        @endif
    </div>
</section>
@endsection
