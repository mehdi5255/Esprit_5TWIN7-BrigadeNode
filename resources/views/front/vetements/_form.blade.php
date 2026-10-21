@php $input = 'w-full rounded-xl border bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-forest-500 focus:ring-4 focus:ring-forest-100'; @endphp
@csrf
<div class="grid gap-5 sm:grid-cols-2">
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Nom du vêtement</span>
        <input name="nom" value="{{ old('nom', $vetement->nom) }}" placeholder="Ex. Veste en jean" required class="{{ $input }} {{ $errors->has('nom') ? 'border-red-400' : 'border-slate-200' }}">
        @error('nom')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
    </label>
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Catégorie</span>
        <select name="categorie_id" required class="{{ $input }} {{ $errors->has('categorie_id') ? 'border-red-400' : 'border-slate-200' }}">
            <option value="">Choisir…</option>
            @foreach($categories as $categorie)<option value="{{ $categorie->id }}" @selected(old('categorie_id', $vetement->categorie_id) == $categorie->id)>{{ $categorie->libelle }}</option>@endforeach
        </select>
        @error('categorie_id')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
    </label>
</div>
<div class="grid gap-5 sm:grid-cols-2">
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Taille</span>
        <input name="taille" list="tailles" value="{{ old('taille', $vetement->taille) }}" placeholder="Ex. M, 38, 10 ans" class="{{ $input }} border-slate-200">
        <datalist id="tailles">@foreach(['XS','S','M','L','XL','XXL','Unique'] as $taille)<option value="{{ $taille }}">@endforeach</datalist>
    </label>
    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Couleur</span><input name="couleur" value="{{ old('couleur', $vetement->couleur) }}" placeholder="Ex. Bleu" class="{{ $input }} border-slate-200"></label>
</div>
<label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">État</span>
    <select name="etat" required class="{{ $input }} {{ $errors->has('etat') ? 'border-red-400' : 'border-slate-200' }}">
        @foreach(\App\Enums\EtatVetement::cases() as $etat)<option value="{{ $etat->value }}" @selected(old('etat', $vetement->etat?->value ?? 'bon') === $etat->value)>{{ $etat->label() }}</option>@endforeach
    </select>
    @error('etat')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
</label>
@include('front.vetements._composition')
<label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Description</span><textarea name="description" rows="4" class="{{ $input }} border-slate-200" placeholder="Particularités, défauts, histoire de la pièce...">{{ old('description', $vetement->description) }}</textarea></label>
<label class="grid cursor-pointer place-items-center rounded-2xl border-2 border-dashed {{ $errors->has('photo') ? 'border-red-300' : 'border-slate-200' }} px-6 py-8 text-center hover:border-forest-400 hover:bg-forest-50">
    <img data-preview src="{{ $vetement->photoUrl() }}" alt="" class="mb-3 h-40 rounded-xl object-cover" @if(! $vetement->photoUrl()) hidden @endif>
    <i data-lucide="image-plus" class="mb-3 h-7 w-7 text-forest-600"></i>
    <span class="text-sm font-semibold">{{ $vetement->photo ? 'Remplacer la photo' : 'Ajouter une photo' }}</span>
    <span class="mt-1 text-xs text-slate-400">PNG ou JPG, 5 Mo maximum</span>
    <input type="file" name="photo" accept="image/*" data-max-ko="5120" class="sr-only" onchange="const p=this.closest('label').querySelector('[data-preview]'); if(this.files[0]){p.src=URL.createObjectURL(this.files[0]); p.hidden=false;}">
    @error('photo')<span class="mt-2 block text-xs text-red-600">{{ $message }}</span>@enderror
</label>
<div class="flex justify-end gap-3">
    <x-button :href="$vetement->exists ? route('vetements.show', $vetement) : route('vetements.index')" label="Annuler" variant="secondary" />
    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>{{ $submit }}</button>
</div>
