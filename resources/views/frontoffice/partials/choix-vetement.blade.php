{{-- Choix d'un vêtement disponible. Attend $vetements et $selection. --}}
<fieldset>
    <legend class="mb-3 text-sm font-semibold text-slate-700">Vêtement <span class="text-red-500">*</span></legend>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach($vetements as $vetement)
        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 has-[:checked]:border-forest-500 has-[:checked]:bg-forest-50">
            <input type="radio" name="vetement_id" value="{{ $vetement->id }}" required @checked(old('vetement_id', $selection) == $vetement->id) class="text-forest-600 focus:ring-forest-500">
            @if($vetement->photoUrl())<img src="{{ $vetement->photoUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover">@endif
            <span><span class="block text-sm font-semibold">{{ $vetement->nom }}</span><span class="text-xs text-slate-500">{{ $vetement->etat->label() }}{{ $vetement->taille ? ' · '.$vetement->taille : '' }}</span></span>
        </label>
        @endforeach
    </div>
    @error('vetement_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
</fieldset>
