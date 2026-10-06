{{-- Valider / désactiver un atelier ou une association. Attend $structure, $type, $compact. --}}
@php $bouton = $compact ? 'rounded-lg px-2.5 py-1.5 text-xs font-semibold' : 'rounded-xl px-4 py-2.5 text-sm font-semibold'; @endphp
<div class="inline-flex gap-2">
@if($structure->estActif())
    <form method="POST" action="{{ route("admin.{$type}.desactiver", $structure->id) }}" data-confirmer="Désactiver {{ $structure->nom }} ? Il ne sera plus visible et ne recevra plus de demandes.">@csrf<button class="{{ $bouton }} text-amber-700 hover:bg-amber-50">Désactiver</button></form>
@else
    <form method="POST" action="{{ route("admin.{$type}.valider", $structure->id) }}">@csrf<button class="{{ $bouton }} bg-forest-600 text-white hover:bg-forest-700">{{ $structure->statut === \App\Enums\StatutStructure::EN_ATTENTE ? 'Valider' : 'Réactiver' }}</button></form>
@endif
</div>
