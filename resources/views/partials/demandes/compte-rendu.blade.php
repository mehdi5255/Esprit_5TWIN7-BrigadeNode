{{-- Détails techniques d'une Reparation. Attend $reparation. --}}
<dl class="mt-4 space-y-4 text-sm">
    @foreach(['Diagnostic' => $reparation->diagnostic, 'Actions réalisées' => $reparation->actions_realisees, 'Pièces utilisées' => $reparation->pieces_utilisees] as $label => $valeur)
        @if($valeur)<div><dt class="font-semibold text-slate-700">{{ $label }}</dt><dd class="mt-1 whitespace-pre-line text-slate-600">{{ $valeur }}</dd></div>@endif
    @endforeach
    @if($reparation->duree_reelle)<div><dt class="font-semibold text-slate-700">Durée</dt><dd class="mt-1 text-slate-600">{{ intdiv($reparation->duree_reelle, 60) ? intdiv($reparation->duree_reelle, 60).' h ' : '' }}{{ $reparation->duree_reelle % 60 }} min</dd></div>@endif
</dl>
@if($reparation->photoAvantUrl() || $reparation->photoApresUrl())
<div class="mt-5 grid grid-cols-2 gap-3">
    @foreach(['Avant' => $reparation->photoAvantUrl(), 'Après' => $reparation->photoApresUrl()] as $label => $url)
        @if($url)<figure><img src="{{ $url }}" alt="Photo {{ mb_strtolower($label) }} réparation" class="aspect-square w-full rounded-2xl object-cover"><figcaption class="mt-1 text-center text-xs text-slate-500">{{ $label }}</figcaption></figure>@endif
    @endforeach
</div>
@endif
