{{-- Sélection d'une note de 1 à 5 étoiles. Attend $noteActuelle (int|null). --}}
<fieldset>
    <legend class="mb-2 text-sm font-semibold text-slate-700">Note <span class="text-red-500">*</span></legend>
    {{-- Ordre inversé + « peer » : cocher une étoile colore aussi celles qui la précèdent visuellement. --}}
    <div class="flex flex-row-reverse justify-end gap-1">
        @for($i = 5; $i >= 1; $i--)
        @php $id = 'note-'.($noteActuelle ? 'modif-' : '').$i; @endphp
        <input type="radio" name="note" id="{{ $id }}" value="{{ $i }}" class="peer sr-only" required @checked(old('note', $noteActuelle) == $i)>
        <label for="{{ $id }}" class="cursor-pointer text-slate-300 hover:text-amber-400 peer-checked:text-amber-400" title="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}"><i data-lucide="star" class="h-8 w-8 fill-current"></i></label>
        @endfor
    </div>
    @error('note')<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@enderror
</fieldset>
