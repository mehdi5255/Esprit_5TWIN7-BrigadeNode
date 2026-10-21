{{-- Facture imprimable d'une réparation : document autonome, sans menu, pensé pour l'impression. Attend $demande et $retour. --}}
@php
    $facture = $demande->facture;
    $euros = fn ($montant) => number_format((float) $montant, 2, ',', ' ').' €';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $facture->numero() }} — TextileCycle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('front/js/tailwind.config.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-100 font-sans text-slate-800 print:bg-white">
    <div class="mx-auto max-w-3xl px-4 py-8 print:max-w-none print:p-0">
        <div class="mb-6 flex items-center justify-between print:hidden">
            <a href="{{ $retour }}" class="text-sm font-semibold text-slate-500 hover:text-forest-700">← Retour à la demande</a>
            <button onclick="window.print()" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white hover:bg-forest-700">Imprimer</button>
        </div>

        <article class="rounded-2xl bg-white p-10 shadow-sm print:rounded-none print:p-0 print:shadow-none">
            <header class="flex flex-wrap items-start justify-between gap-6 border-b border-slate-200 pb-6">
                <div>
                    <p class="font-display text-xl font-extrabold">{{ $demande->atelier->nom }}</p>
                    @if($demande->atelier->adresseComplete())<p class="mt-1 text-sm text-slate-500">{{ $demande->atelier->adresseComplete() }}</p>@endif
                    <p class="text-sm text-slate-500">{{ collect([$demande->atelier->telephone, $demande->atelier->email])->filter()->implode(' · ') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-display text-3xl font-bold text-forest-700">FACTURE</p>
                    <p class="mt-1 font-semibold">{{ $facture->numero() }}</p>
                    <p class="text-sm text-slate-500">Émise le {{ $facture->date_emission->format('d/m/Y') }}</p>
                </div>
            </header>

            <section class="grid gap-6 py-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Client</p>
                    <p class="mt-1 font-semibold">{{ $demande->particulier->name }}</p>
                    <p class="text-sm text-slate-500">{{ $demande->particulier->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Objet</p>
                    <p class="mt-1 font-semibold">{{ $demande->type_reparation->label() }} — {{ $demande->vetement->nom }}</p>
                    <p class="text-sm text-slate-500">Demande {{ $demande->reference() }}@if($demande->devis) · devis {{ $demande->devis->numero() }}@endif</p>
                </div>
            </section>

            <table class="w-full text-sm">
                <thead><tr class="border-y border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400"><th class="py-3">Désignation</th><th class="py-3 text-right">Montant</th></tr></thead>
                <tbody>
                    <tr class="border-b border-slate-100">
                        <td class="py-3">Main-d'œuvre selon devis{{ $demande->estRetourSousGarantie() ? ' — retour sous garantie' : '' }}@if($demande->reparation?->actions_realisees)<span class="block text-xs text-slate-500">{{ $demande->reparation->actions_realisees }}</span>@endif</td>
                        <td class="py-3 text-right">{{ $euros($facture->montant_devis) }}</td>
                    </tr>
                    @if($facture->supplement > 0)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">Supplément<span class="block text-xs text-slate-500">{{ $facture->motif_supplement }}</span></td>
                        <td class="py-3 text-right">{{ $euros($facture->supplement) }}</td>
                    </tr>
                    @endif
                </tbody>
                <tfoot><tr><td class="pt-4 text-right font-semibold">Total</td><td class="pt-4 text-right font-display text-xl font-bold">{{ $euros($facture->montant_total) }}</td></tr></tfoot>
            </table>

            <footer class="mt-8 space-y-2 border-t border-slate-200 pt-6 text-sm">
                <p><span class="font-semibold">Statut :</span> {{ $facture->estPayee() ? 'payée le '.$facture->date_paiement->format('d/m/Y') : 'à régler lors de la récupération du vêtement' }}</p>
                @if($facture->garantie)
                <p><span class="font-semibold">Garantie :</span> {{ $facture->garantie->duree_mois }} mois, jusqu'au {{ $facture->garantie->date_fin->format('d/m/Y') }}. {{ $facture->garantie->conditions }}</p>
                @endif
                <p class="pt-4 text-xs text-slate-400">Facture établie via TextileCycle — donnez une seconde vie à vos vêtements.</p>
            </footer>
        </article>
    </div>
</body>
</html>
