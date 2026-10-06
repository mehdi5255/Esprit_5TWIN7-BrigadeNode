{{-- Avis du particulier sur une demande terminée : formulaire, ou avis déjà laissé (modifiable / supprimable). Attend $demande et $type (reparations|transformations|dons). --}}
@if($review = $demande->review)
    <div class="rounded-3xl border border-amber-200 bg-amber-50/50 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-lg font-bold">Votre avis</h2>
            @if($review->statut->value === 'masque')<span class="text-xs font-semibold text-slate-500">Masqué par la modération</span>@endif
        </div>
        <div class="mt-3">@include('components.etoiles', ['note' => $review->note])</div>
        @if($review->commentaire)<p class="mt-3 text-sm text-slate-600">“{{ $review->commentaire }}”</p>@endif
        @if($review->reponse)
            <div class="mt-4 rounded-2xl bg-white p-4 text-sm"><p class="font-semibold">Réponse de {{ $demande->structureModel()->nom }}</p><p class="mt-1 text-slate-600">{{ $review->reponse }}</p></div>
        @endif
        <div class="mt-4 flex flex-wrap items-start gap-4 text-sm">
            <details class="flex-1" @if($errors->any()) open @endif>
                <summary class="cursor-pointer font-semibold text-forest-700">Modifier mon avis</summary>
                <form method="POST" action="{{ route('avis.update', $review) }}" class="mt-4 space-y-4 rounded-2xl bg-white p-4">
                    @csrf @method('PUT')
                    @include('partials.demandes.choix-note', ['noteActuelle' => $review->note])
                    @include('components.champ', ['label' => 'Commentaire', 'name' => 'commentaire', 'type' => 'textarea', 'value' => $review->commentaire])
                    <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 font-semibold text-white hover:bg-forest-700"><i data-lucide="save" class="h-4 w-4"></i>Enregistrer</button>
                </form>
            </details>
            <form method="POST" action="{{ route('avis.destroy', $review) }}" data-confirmer="Supprimer votre avis ?">@csrf @method('DELETE')<button class="font-semibold text-red-600 hover:underline">Supprimer</button></form>
        </div>
    </div>
@elseif($demande->peutEtreNotee())
    <form method="POST" action="{{ route('avis.store', [$type, $demande->id]) }}" class="space-y-4 rounded-3xl border border-amber-200 bg-amber-50/50 p-6">
        @csrf
        <h2 class="font-display text-lg font-bold">Donnez votre avis sur {{ $demande->structureModel()->nom }}</h2>
        @include('partials.demandes.choix-note', ['noteActuelle' => null])
        @include('components.champ', ['label' => 'Commentaire', 'name' => 'commentaire', 'type' => 'textarea', 'placeholder' => 'Qu’avez-vous pensé du service ?'])
        <button class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="send" class="h-4 w-4"></i>Publier mon avis</button>
    </form>
@endif
