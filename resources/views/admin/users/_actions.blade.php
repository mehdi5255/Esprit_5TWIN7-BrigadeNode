{{-- Actions de l'admin sur un compte. Attend $user, $compact (bool). --}}
@php $bouton = $compact ? 'rounded-lg px-2.5 py-1.5 text-xs font-semibold' : 'rounded-xl px-4 py-2.5 text-sm font-semibold'; @endphp
@if($user->estEnAttente())
    <form method="POST" action="{{ route('admin.users.valider', $user) }}">@csrf<button class="{{ $bouton }} bg-forest-600 text-white hover:bg-forest-700">Valider</button></form>
@endif
@unless($user->hasRole('admin'))
    @if($user->estBloque())
        <form method="POST" action="{{ route('admin.users.debloquer', $user) }}">@csrf<button class="{{ $bouton }} border border-slate-200 text-slate-700 hover:bg-slate-50">Débloquer</button></form>
    @else
        <form method="POST" action="{{ route('admin.users.bloquer', $user) }}" data-confirmer="Bloquer le compte de {{ $user->name }} ?">@csrf<button class="{{ $bouton }} text-amber-700 hover:bg-amber-50">Bloquer</button></form>
    @endif
    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirmer="Supprimer définitivement le compte de {{ $user->name }} ?">@csrf @method('DELETE')<button class="{{ $compact ? 'rounded-lg p-2' : 'rounded-xl px-4 py-2.5 text-sm font-semibold' }} text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Supprimer"><i data-lucide="trash-2" class="h-4 w-4 {{ $compact ? '' : 'mr-1 inline' }}"></i>{{ $compact ? '' : 'Supprimer' }}</button></form>
@endunless
