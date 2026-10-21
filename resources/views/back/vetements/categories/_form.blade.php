@php $input = 'w-full rounded-xl border bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-forest-500 focus:ring-4 focus:ring-forest-100'; @endphp
@csrf
<label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Libellé</span>
    <input name="libelle" value="{{ old('libelle', $categorie->libelle) }}" required placeholder="Ex. Manteau" class="{{ $input }} {{ $errors->has('libelle') ? 'border-red-400' : 'border-slate-200' }}">
    @error('libelle')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
</label>
<label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Description</span>
    <input name="description" value="{{ old('description', $categorie->description) }}" placeholder="Ex. Vestes, manteaux, doudounes" class="{{ $input }} border-slate-200">
</label>
<label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Icône</span>
    <input name="icone" value="{{ old('icone', $categorie->icone) }}" placeholder="shirt" class="{{ $input }} {{ $errors->has('icone') ? 'border-red-400' : 'border-slate-200' }}">
    @error('icone')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@else<span class="mt-1.5 block text-xs text-slate-400">Nom d'icône Lucide (lucide.dev/icons), ex. shirt, footprints.</span>@enderror
</label>
<label class="flex items-center gap-3 text-sm"><input type="checkbox" name="actif" value="1" @checked(old('actif', $categorie->actif)) class="rounded border-slate-300 text-forest-600 focus:ring-forest-500">Active (proposée aux particuliers)</label>
<div class="flex gap-3">
    @if($categorie->exists)<x-button :href="route('admin.categories.index')" label="Annuler" variant="secondary" />@endif
    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>{{ $submit }}</button>
</div>
