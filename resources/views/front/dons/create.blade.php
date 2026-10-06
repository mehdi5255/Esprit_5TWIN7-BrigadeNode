@extends('layouts.front')
@section('title', 'Faire un don')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route('dons.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à mes dons</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-rose-50 text-rose-600"><i data-lucide="heart-handshake" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">Faire un don</h1><p class="mt-2 text-slate-500">Choisissez une association et, si possible, l'un de ses besoins actuels.</p></div>
        @if($vetements->isEmpty())
            @include('partials.front.aucun-vetement')
        @elseif($associations->isEmpty())
            <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Aucune association n'accepte de dons pour le moment.</p>
        @else
        <form method="POST" action="{{ route('dons.store') }}" class="space-y-6" data-don>
            @csrf
            @include('partials.front.choix-vetement')
            <x-champ label="Association" name="association_id" type="select" :required="true" vide="Choisir une association…" :value="$associationChoisie" :options="$associations->mapWithKeys(fn ($a) => [$a->id => $a->nom.($a->ville ? ' ('.$a->ville.')' : '')])" />
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Besoin concerné</span>
                <select name="besoin_id" class="w-full rounded-xl border {{ $errors->has('besoin_id') ? 'border-red-400' : 'border-slate-200' }} bg-white px-4 py-3 text-sm outline-none focus:border-forest-500 focus:ring-4 focus:ring-forest-100">
                    <option value="">Don libre (pas de besoin précis)</option>
                    @foreach($associations as $association)
                        @foreach($association->besoinsActifs as $besoin)
                        <option value="{{ $besoin->id }}" data-association="{{ $association->id }}" @selected(old('besoin_id', $besoinChoisi) == $besoin->id)>{{ $besoin->titre }} — {{ $besoin->priorite->label() }}</option>
                        @endforeach
                    @endforeach
                </select>
                @error('besoin_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@else<span class="mt-1.5 block text-xs text-slate-400">Seuls les besoins de l'association choisie sont proposés.</span>@enderror
            </label>
            <x-champ label="Message à l’association" name="message" type="textarea" placeholder="Ex. Manteau porté deux hivers, lavé et en très bon état." />
            <div class="flex justify-end gap-3">
                <x-button :href="route('dons.index')" label="Annuler" variant="secondary" />
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Proposer le don</button>
            </div>
        </form>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Ne propose que les besoins de l'association sélectionnée.
    const formDon = document.querySelector('[data-don]');
    if (formDon) {
        const association = formDon.querySelector('[name=association_id]');
        const besoin = formDon.querySelector('[name=besoin_id]');
        const filtrer = () => {
            besoin.querySelectorAll('option[data-association]').forEach(option => {
                option.hidden = option.dataset.association !== association.value;
                if (option.hidden && option.selected) besoin.value = '';
            });
        };
        association.addEventListener('change', filtrer);
        filtrer();
    }
</script>
@endpush
