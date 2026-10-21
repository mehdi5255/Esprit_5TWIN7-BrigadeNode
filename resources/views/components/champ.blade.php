{{--
    Champ de formulaire avec libellé, ancienne valeur et erreur de validation.
    Paramètres : label, name, type (text|email|number|date|textarea|select|file), value, options (valeur => libellé),
                 required, placeholder, help, rows, vide (option vide d'un select).
                 Les autres attributs HTML (min, step, accept, autocomplete…) s'écrivent directement sur la balise : <x-champ … min="0" />
--}}
@props(['label', 'name', 'type' => 'text', 'value' => null, 'options' => [], 'required' => false, 'placeholder' => null, 'help' => null, 'rows' => 3, 'vide' => null])
@php
    $type = $type ?? 'text';
    $cle = str_replace(['[', ']'], ['.', ''], $name);
    $valeur = old($cle, $value ?? null);
    $valeur = $valeur instanceof \BackedEnum ? $valeur->value : $valeur;
    $classes = 'w-full rounded-xl border bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-forest-500 focus:ring-4 focus:ring-forest-100 '
        . ($errors->has($cle) ? 'border-red-400' : 'border-slate-200');
    $requis = ($required ?? false) ? 'required' : '';
@endphp
<label class="block">
    <span class="mb-2 block text-sm font-semibold text-slate-700">{{ $label }}@if($required ?? false) <span class="text-red-500">*</span>@endif</span>
    @if($type === 'textarea')
        <textarea name="{{ $name }}" rows="{{ $rows ?? 3 }}" placeholder="{{ $placeholder ?? '' }}" class="{{ $classes }}" {{ $requis }} {{ $attributes }}>{{ $valeur }}</textarea>
    @elseif($type === 'select')
        <select name="{{ $name }}" class="{{ $classes }}" {{ $requis }} {{ $attributes }}>
            @isset($vide)<option value="">{{ $vide }}</option>@endisset
            @foreach($options as $optionValeur => $optionLibelle)<option value="{{ $optionValeur }}" @selected((string) $valeur === (string) $optionValeur)>{{ $optionLibelle }}</option>@endforeach
        </select>
    @elseif($type === 'file')
        <input type="file" name="{{ $name }}" class="block w-full rounded-xl border border-dashed {{ $errors->has($cle) ? 'border-red-400' : 'border-slate-300' }} bg-white p-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-forest-50 file:px-3 file:py-1.5 file:font-semibold file:text-forest-700" {{ $requis }} {{ $attributes }}>
    @else
        <input type="{{ $type }}" name="{{ $name }}" value="{{ $valeur }}" placeholder="{{ $placeholder ?? '' }}" class="{{ $classes }}" {{ $requis }} {{ $attributes }}>
    @endif
    @error($cle)<span class="mt-1.5 block text-xs text-red-600">{{ $message }}</span>@else @isset($help)<span class="mt-1.5 block text-xs text-slate-400">{{ $help }}</span>@endisset @enderror
</label>
