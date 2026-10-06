{{-- Cadre commun des formulaires de modification d'une demande. Attend $demande, $segment, $titre, $structure, $couleur, $icone ; section « champs ». --}}
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route($segment.'.show', $demande) }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-forest-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Retour à la demande</a>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        <div class="mb-8"><span class="mb-4 grid h-12 w-12 place-items-center rounded-2xl {{ $couleur }}"><i data-lucide="{{ $icone }}" class="h-6 w-6"></i></span><h1 class="font-display text-3xl font-bold">{{ $titre }}</h1><p class="mt-2 text-slate-500">{{ $demande->reference() }} · modifiable tant que la demande n'a pas été prise en charge.</p></div>
        <div class="mb-6 flex items-center gap-4 rounded-2xl bg-slate-50 p-4 text-sm">
            @if($demande->vetement->photoUrl())<img src="{{ $demande->vetement->photoUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover">@endif
            <div><p class="font-semibold">{{ $demande->vetement->nom }}</p><p class="text-slate-500">{{ $structure }}</p></div>
        </div>
        <form method="POST" action="{{ route($segment.'.update', $demande) }}" class="space-y-6">
            @csrf @method('PUT')
            {{ $champs }}
            <div class="flex justify-end gap-3">
                @include('components.button', ['href' => route($segment.'.show', $demande), 'label' => 'Annuler', 'variant' => 'secondary'])
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700"><i data-lucide="save" class="h-4 w-4"></i>Enregistrer</button>
            </div>
        </form>
    </div>
</section>
