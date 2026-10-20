@extends('layouts.back')
@use('App\Enums\StatutDemande')
@section('title', 'Demande ' . $demande->reference())
@section('breadcrumb', ($espace === 'admin' ? 'Supervision' : 'Demandes') . ' / Réparations / ' . $demande->reference())
@section('content')
@php
    $primaire = 'inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-forest-700';
    $action = fn ($nom) => route("atelier.reparations.{$nom}", $demande);
@endphp
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        @include('partials.back.demandes.entete', [
            'texte' => $demande->type_reparation->label().' — '.$demande->description,
            'details' => [
                'Budget max' => $demande->budget_max ? number_format($demande->budget_max, 2, ',', ' ').' €' : '—',
                'Date souhaitée' => $demande->date_souhaitee?->format('d/m/Y') ?? '—',
                'Atelier' => $demande->atelier->nom,
            ],
        ])
        @include('front.reparations._photo-probleme')
        @if($demande->reparation)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">Réparation</h2>
            @include('partials.demandes.compte-rendu', ['reparation' => $demande->reparation])
        </section>
        @endif
        <div class="grid gap-6 lg:grid-cols-2">
            @include('front.reparations._documents', ['espace' => $espace])
        </div>
        @include('partials.back.demandes.avis')
    </div>

    <aside>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-display text-lg font-bold">{{ $espace === 'admin' ? 'Statut' : 'Prochaine action' }}</h2>
            <div class="mt-4 space-y-4 text-sm">
            @if($espace === 'admin')
                <p class="text-slate-500">Cette demande est traitée par <span class="font-semibold text-slate-700">{{ $demande->atelier->nom }}</span>. L'administration consulte sans intervenir.</p>
            @else
            @switch($demande->statut)
                @case(StatutDemande::EN_ATTENTE)
                    @include('partials.back.demandes.decision', ['texte' => $demande->estRetourSousGarantie() ? 'Retour sous garantie : acceptez-le pour que le particulier dépose le vêtement. Aucun frais ne lui sera facturé.' : 'Acceptez ou refusez la demande. Le devis sera établi après le dépôt, une fois le vêtement diagnostiqué.', 'routeAccepter' => $action('accepter'), 'routeRefuser' => $action('refuser')])
                    @break
                @case(StatutDemande::ACCEPTEE)
                    <p class="text-slate-500">En attente du dépôt du vêtement par le particulier.</p>
                    <form method="POST" action="{{ $action('depot') }}">@csrf<button class="{{ $primaire }}"><i data-lucide="package-check" class="h-4 w-4"></i>Confirmer la remise du vêtement</button></form>
                    @break
                @case(StatutDemande::DEPOT_REMIS)
                    @if(! $demande->devis)
                        <p class="text-slate-500">{{ $demande->estRetourSousGarantie() ? 'Diagnostiquez le vêtement : la réparation est sous garantie, sans devis à payer.' : 'Diagnostiquez le vêtement, puis établissez le devis. Le particulier devra l’accepter avant la réparation.' }}</p>
                        <form method="POST" action="{{ route('atelier.reparations.devis.etablir', $demande) }}" enctype="multipart/form-data" class="space-y-4" data-devis>@csrf
                            <x-champ label="Diagnostic" name="diagnostic" type="textarea" :required="true" placeholder="Ex. Fermeture éclair cassée, dents arrachées sur 5 cm." />
                            <x-champ label="Photo avant" name="photo_avant" type="file" accept="image/*" data-max-ko="5120" />
                            @unless($demande->estRetourSousGarantie())
                            <div class="rounded-xl border border-violet-200 bg-violet-50 p-3">
                                <button type="button" data-estimer data-url="{{ route('atelier.reparations.estimation', $demande) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-violet-700 ring-1 ring-violet-200 hover:bg-violet-100"><i data-lucide="sparkles" class="h-4 w-4"></i>Estimer le montant (IA)</button>
                                <div data-estimation class="mt-3 hidden text-xs text-violet-900"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <x-champ label="Montant (€)" name="main_oeuvre" type="number" :required="true" min="0" step="0.5" />
                                <x-champ label="Délai (jours)" name="delai_jours" type="number" min="1" placeholder="Ex. 3" />
                            </div>
                            <x-champ label="Devis valable jusqu'au" name="valide_jusqu_au" type="date" :required="true" :value="now()->addDays(\App\Models\Reparations\Devis::VALIDITE_JOURS)->toDateString()" :min="now()->toDateString()" />
                            @if($demande->budget_max)<p class="text-xs text-slate-400">Budget maximum indiqué par le particulier : {{ number_format($demande->budget_max, 2, ',', ' ') }} €</p>@endif
                            @else
                            <x-champ label="Délai estimé (jours)" name="delai_jours" type="number" min="1" />
                            @endunless
                            <button class="{{ $primaire }}"><i data-lucide="file-check" class="h-4 w-4"></i>{{ $demande->estRetourSousGarantie() ? 'Valider le diagnostic' : 'Envoyer le devis au particulier' }}</button>
                        </form>
                    @elseif($demande->devis->statut === \App\Enums\StatutDevis::ACCEPTE)
                        <p class="text-slate-500">{{ $demande->estRetourSousGarantie() ? 'Réparation sous garantie, sans frais.' : 'Devis accepté par le particulier.' }} Vous pouvez démarrer la réparation.</p>
                        <form method="POST" action="{{ $action('demarrer') }}">@csrf<button class="{{ $primaire }}"><i data-lucide="play" class="h-4 w-4"></i>Démarrer la réparation</button></form>
                    @elseif($demande->devis->estExpire())
                        <p class="text-slate-500">Le devis a expiré sans réponse. Modifiez-le (carte « Devis ») pour prolonger sa validité.</p>
                    @else
                        <p class="text-slate-500">Devis envoyé : en attente de la réponse du particulier. La réparation démarrera dès qu'il l'aura accepté.</p>
                    @endif
                    @break
                @case(StatutDemande::EN_TRAITEMENT)
                    <form method="POST" action="{{ $action('terminer') }}" enctype="multipart/form-data" class="space-y-4">@csrf
                        <x-champ label="Actions réalisées" name="actions_realisees" type="textarea" :required="true" />
                        <x-champ label="Pièces utilisées" name="pieces_utilisees" placeholder="Ex. Fil coton, 2 boutons" />
                        <x-champ label="Durée (min)" name="duree_reelle" type="number" min="1" />
                        <x-champ label="Photo après" name="photo_apres" type="file" accept="image/*" data-max-ko="5120" />
                        <button class="{{ $primaire }}"><i data-lucide="file-check" class="h-4 w-4"></i>Générer la facture</button>
                    </form>
                    @break
                @case(StatutDemande::PRET)
                    <p class="text-slate-500">Le particulier a été prévenu que son vêtement est prêt. À la récupération, encaissez la facture de <span class="font-semibold text-slate-700">{{ number_format((float) $demande->facture?->montant_total, 2, ',', ' ') }} €</span>.</p>
                    <form method="POST" action="{{ $action('recuperation') }}" data-confirmer="Confirmer le paiement de {{ number_format((float) $demande->facture?->montant_total, 2, ',', ' ') }} € et la récupération du vêtement ?" data-confirmer-bouton="Confirmer">@csrf<button class="{{ $primaire }}"><i data-lucide="hand-coins" class="h-4 w-4"></i>Confirmer le paiement et la récupération</button></form>
                    @break
                @default
                    <p class="text-slate-500">Cette demande est clôturée ({{ mb_strtolower($demande->statut->label()) }}). Aucune action requise.</p>
            @endswitch
            @endif
            </div>
        </section>
    </aside>
</div>
@endsection

@push('scripts')
<script>
    // Aide au chiffrage : envoie le diagnostic, affiche le montant estimé et les devis comparables.
    document.querySelector('[data-estimer]')?.addEventListener('click', async (e) => {
        const bouton = e.currentTarget;
        const form = bouton.closest('form');
        const zone = form.querySelector('[data-estimation]');
        const euros = n => Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        const diagnostic = form.querySelector('[name=diagnostic]').value.trim();
        zone.classList.remove('hidden');
        if (!diagnostic) { zone.textContent = 'Saisissez d’abord le diagnostic : l’estimation s’appuie dessus.'; return; }
        zone.textContent = 'Analyse du diagnostic et des devis comparables…';
        bouton.disabled = true;
        try {
            const corps = new FormData();
            corps.append('_token', form.querySelector('[name=_token]').value);
            corps.append('diagnostic', diagnostic);
            const reponse = await fetch(bouton.dataset.url, { method: 'POST', body: corps, headers: { Accept: 'application/json' } });
            const r = await reponse.json();
            if (r.montant === null) { zone.textContent = 'Pas assez de données pour estimer : fixez le montant vous-même.'; return; }
            const source = r.comparables.length
                ? '<p class="mt-2 font-semibold">Devis comparables</p><ul class="mt-1 space-y-1">' + r.comparables.map(c => `<li class="flex justify-between gap-2"><span>${esc(c.reference)} · ${esc(c.atelier)} · ${esc(c.type)}${c.diagnostic ? ' — « ' + esc(c.diagnostic) + ' »' : ''}</span><span class="shrink-0 font-semibold">${euros(c.montant)} · ${c.ressemblance} %</span></li>`).join('') + '</ul>'
                : (r.base === 'tarifs' ? '<p class="mt-1">Aucun devis comparable : moyenne de vos tarifs de réparation.</p>' : '<p class="mt-1">Aucun devis comparable dans l’historique.</p>');
            const ia = r.base === 'gemini'
                ? `<p class="mt-1 rounded-lg bg-white/70 p-2 text-sm"><span class="font-semibold text-violet-700">Estimé par Gemini :</span> ${esc(r.explication)}</p>`
                  + (r.statistique !== null ? `<p class="mt-1 text-xs text-slate-500">Moyenne statistique des devis comparables : ${euros(r.statistique)}</p>` : '')
                : '';
            zone.innerHTML = `<p class="text-sm"><span class="font-semibold">Estimation : ${euros(r.montant)}</span> (fourchette ${euros(r.min)} – ${euros(r.max)}) · confiance ${r.confiance}</p>`
                + ia + source
                + '<button type="button" data-utiliser class="mt-2 font-semibold text-violet-700 underline">Utiliser ce montant</button>';
            zone.querySelector('[data-utiliser]').addEventListener('click', () => { form.querySelector('[name=main_oeuvre]').value = r.montant; });
        } catch {
            zone.textContent = 'L’estimation a échoué : fixez le montant vous-même.';
        } finally {
            bouton.disabled = false;
        }
    });
</script>
@endpush
