@extends('layouts.back')
@section('title', 'Rapports')
@section('breadcrumb', 'Supervision / Rapports d’activité')
@section('content')
@php
    $euros = fn ($n) => number_format((float) $n, 2, ',', ' ').' €';
    // Indicateurs de la synthèse : [libellé, valeur affichée, clé de comparaison, icône, couleur].
    $indicateurs = [
        ['Demandes créées', $resume['demandes'], 'demandes', 'clipboard-list', 'blue'],
        ['Demandes terminées', $resume['terminees'], 'terminees', 'circle-check', 'green'],
        ['Taux de réussite', $resume['taux'].' %', 'taux', 'target', 'violet'],
        ['Chiffre d’affaires ateliers', $euros($resume['chiffre']), 'chiffre', 'euro', 'amber'],
        ['Bénéficiaires aidés', $resume['beneficiaires'], 'beneficiaires', 'heart', 'rose'],
        ['Nouvelles inscriptions', $resume['inscriptions'], 'inscriptions', 'user-plus', 'slate'],
    ];
    $pastilles = ['blue' => 'bg-blue-50 text-blue-700', 'green' => 'bg-emerald-50 text-emerald-700', 'violet' => 'bg-violet-50 text-violet-700', 'amber' => 'bg-amber-50 text-amber-700', 'rose' => 'bg-rose-50 text-rose-700', 'slate' => 'bg-slate-100 text-slate-700'];
    $carte = 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm';
    $titreSection = 'mb-4 flex items-center gap-3 font-display text-xl font-bold';
    $numero = 'grid h-8 w-8 place-items-center rounded-lg bg-forest-600 text-sm text-white';
@endphp

{{-- Barre d'outils (masquée à l'impression) --}}
<div class="no-print mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex flex-wrap gap-2 text-sm font-semibold">
        @foreach(\App\Http\Controllers\Admin\RapportController::RACCOURCIS as $cle => $libelle)
        <a href="{{ route('admin.rapports.index', ['periode' => $cle]) }}" class="rounded-full px-4 py-2 {{ $raccourci === $cle ? 'bg-forest-600 text-white' : 'bg-slate-50 text-slate-600 ring-1 ring-slate-200 hover:text-forest-700' }}">{{ $libelle }}</a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('admin.rapports.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="w-44"><x-champ label="Du" name="du" type="date" :value="$du->toDateString()" /></div>
        <div class="w-44"><x-champ label="Au" name="au" type="date" :value="$au->toDateString()" /></div>
        <button class="rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white hover:bg-forest-700">Générer</button>
        <div class="ml-auto flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="file-down" class="h-4 w-4"></i>Exporter en PDF</button>
            <a href="{{ route('admin.rapports.export', ['du' => $du->toDateString(), 'au' => $au->toDateString()]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="download" class="h-4 w-4"></i>Exporter en CSV</a>
        </div>
    </form>
</div>

<div id="rapport" class="space-y-8">
    {{-- En-tête du rapport --}}
    <header class="flex flex-wrap items-end justify-between gap-4 rounded-2xl bg-gradient-to-r from-forest-700 to-forest-500 p-6 text-white shadow-sm">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-100">TextileCycle · Rapport d’activité</p>
            <h2 class="mt-1 font-display text-2xl font-bold">Du {{ $du->translatedFormat('d F Y') }} au {{ $au->translatedFormat('d F Y') }}</h2>
            <p class="mt-1 text-sm text-emerald-100">Comparé à la période précédente : du {{ $avantDu->translatedFormat('d F') }} au {{ $avantAu->translatedFormat('d F Y') }}</p>
        </div>
        <p class="text-sm text-emerald-100">Généré le {{ now()->translatedFormat('d F Y à H:i') }}<br>par {{ auth()->user()->name }}</p>
    </header>

    {{-- 1. Synthèse --}}
    <section>
        <h2 class="{{ $titreSection }}"><span class="{{ $numero }}">1</span>Synthèse</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($indicateurs as [$libelle, $valeur, $cle, $icone, $couleur])
            @php
                [$actuel, $avant] = [$resume[$cle], $precedent[$cle]];
                $ecart = $avant > 0 ? (int) round(($actuel - $avant) / $avant * 100) : null;
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div><p class="text-sm font-medium text-slate-500">{{ $libelle }}</p><p class="mt-2 font-display text-3xl font-bold text-slate-900">{{ $valeur }}</p></div>
                    <span class="grid h-11 w-11 place-items-center rounded-xl {{ $pastilles[$couleur] }}"><i data-lucide="{{ $icone }}" class="h-5 w-5"></i></span>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    @if($ecart === null)
                        <span class="font-semibold text-slate-600">{{ $actuel > 0 ? 'Nouveau' : 'Stable' }}</span> par rapport à la période précédente
                    @else
                        <span class="font-semibold {{ $ecart > 0 ? 'text-emerald-600' : ($ecart < 0 ? 'text-red-600' : 'text-slate-600') }}">{{ $ecart > 0 ? '▲ +' : ($ecart < 0 ? '▼ ' : '= ') }}{{ $ecart }} %</span> par rapport à la période précédente
                    @endif
                </p>
            </article>
            @endforeach
        </div>
    </section>

    {{-- 2. Activité --}}
    <section class="saut-page">
        <h2 class="{{ $titreSection }}"><span class="{{ $numero }}">2</span>Activité</h2>
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="{{ $carte }} xl:col-span-2">
                <h3 class="font-semibold">Évolution des demandes</h3>
                <p class="text-sm text-slate-500">Demandes créées par {{ $graphiques['unite'] }}, par type</p>
                <div class="mt-4 h-72"><canvas id="graphique-evolution"></canvas></div>
            </div>
            <div class="{{ $carte }}">
                <h3 class="font-semibold">Nouvelles inscriptions</h3>
                <p class="text-sm text-slate-500">Comptes créés par {{ $graphiques['unite'] }}</p>
                <div class="mt-4 h-72"><canvas id="graphique-inscriptions"></canvas></div>
            </div>
        </div>
    </section>

    {{-- 3. Répartition et résultats --}}
    <section>
        <h2 class="{{ $titreSection }}"><span class="{{ $numero }}">3</span>Répartition et résultats</h2>
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="{{ $carte }}">
                <h3 class="font-semibold">Demandes par type</h3>
                <div class="mt-4 h-64"><canvas id="graphique-types"></canvas></div>
            </div>
            <div class="{{ $carte }} xl:col-span-2">
                <h3 class="font-semibold">Issue des demandes par type</h3>
                <div class="mt-4 h-64"><canvas id="graphique-statuts"></canvas></div>
            </div>
        </div>
        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Type</th><th class="px-6 py-3">Total</th><th class="px-6 py-3">Terminées</th><th class="px-6 py-3">En cours</th><th class="px-6 py-3">Refusées</th><th class="px-6 py-3">Annulées</th><th class="px-6 py-3">Taux de réussite</th><th class="px-6 py-3">Chiffre d’affaires</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($parType as $libelle => $ligne)
                    <tr><td class="px-6 py-4 font-semibold">{{ $libelle }}</td><td class="px-6 py-4">{{ $ligne['total'] }}</td><td class="px-6 py-4">{{ $ligne['terminees'] }}</td><td class="px-6 py-4">{{ $ligne['en_cours'] }}</td><td class="px-6 py-4">{{ $ligne['refusees'] }}</td><td class="px-6 py-4">{{ $ligne['annulees'] }}</td><td class="px-6 py-4">{{ $ligne['total'] ? round($ligne['terminees'] / $ligne['total'] * 100).' %' : '—' }}</td><td class="px-6 py-4">{{ $libelle === 'Don' ? '—' : $euros($ligne['chiffre']) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    </section>

    {{-- 4. Chiffre d'affaires --}}
    <section class="saut-page">
        <h2 class="{{ $titreSection }}"><span class="{{ $numero }}">4</span>Chiffre d’affaires des ateliers</h2>
        <div class="{{ $carte }}">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <p class="text-sm text-slate-500">Montant des réparations et transformations terminées, par {{ $graphiques['unite'] }} de création de la demande</p>
                <p class="font-display text-2xl font-bold text-forest-700">{{ $euros($resume['chiffre']) }}</p>
            </div>
            <div class="mt-4 h-72"><canvas id="graphique-chiffre"></canvas></div>
        </div>
    </section>

    {{-- 5. Impact écologique --}}
    <section>
        <h2 class="{{ $titreSection }}"><span class="{{ $numero }}">5</span>Impact écologique depuis le lancement</h2>
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl bg-forest-900 p-6 text-white">
                <p class="text-xs text-emerald-100/70">Estimation à partir de la composition et du poids des vêtements sauvés</p>
                <div class="mt-5 space-y-5">
                    <div><p class="font-display text-3xl font-bold">{{ $impact['vetements'] }}</p><p class="text-sm text-emerald-100/70">vêtements sauvés</p></div>
                    <div><p class="font-display text-3xl font-bold">≈ {{ number_format($impact['co2'], 1, ',', ' ') }} kg</p><p class="text-sm text-emerald-100/70">de CO₂ évités</p></div>
                    <div><p class="font-display text-3xl font-bold">≈ {{ number_format($impact['eau'], 0, ',', ' ') }} L</p><p class="text-sm text-emerald-100/70">d'eau économisés</p></div>
                </div>
            </div>
            <div class="{{ $carte }} xl:col-span-2">
                <h3 class="font-semibold">Matières les plus sauvées</h3>
                <p class="text-sm text-slate-500">Kilos de matière remis en circulation</p>
                @if($matieresSauvees->isEmpty())
                    <p class="mt-6 text-sm text-slate-500">Aucun vêtement sauvé avec une composition renseignée.</p>
                @else
                    <div class="mt-4 h-64"><canvas id="graphique-matieres"></canvas></div>
                @endif
            </div>
        </div>
    </section>

    {{-- 6. Acteurs --}}
    <section class="saut-page">
        <h2 class="{{ $titreSection }}"><span class="{{ $numero }}">6</span>Acteurs les plus actifs</h2>
        <div class="grid gap-6 xl:grid-cols-2">
            <div class="{{ $carte }}">
                <h3 class="font-semibold">Ateliers</h3>
                <p class="text-sm text-slate-500">Réparations et transformations récupérées sur la période</p>
                @if($topAteliers->isEmpty())<p class="mt-6 text-sm text-slate-500">Aucune demande terminée.</p>
                @else<div class="mt-4 h-60"><canvas id="graphique-ateliers"></canvas></div>@endif
            </div>
            <div class="{{ $carte }}">
                <h3 class="font-semibold">Associations</h3>
                <p class="text-sm text-slate-500">Dons remis aux bénéficiaires sur la période</p>
                @if($topAssociations->isEmpty())<p class="mt-6 text-sm text-slate-500">Aucun don remis.</p>
                @else<div class="mt-4 h-60"><canvas id="graphique-associations"></canvas></div>@endif
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    @media print {
        @page { size: A4; margin: 12mm; }
        body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body > :not(.lg\:pl-64), .lg\:pl-64 > :not(main), main > div > .mb-7, .no-print { display: none !important; }
        .lg\:pl-64 { padding-left: 0 !important; }
        main { padding: 0 !important; }
        .saut-page { break-before: page; }
        #rapport section, #rapport header { break-inside: avoid; }
        #rapport .shadow-sm { box-shadow: none !important; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // Graphiques du rapport (Chart.js). Les données viennent du contrôleur.
    (() => {
        const g = @json($graphiques);
        const couleurs = { 'Réparation': '#d97706', 'Transformation': '#7c3aed', 'Don': '#e11d48' };
        const statuts = { 'Terminées': '#16a34a', 'En cours': '#3b82f6', 'Refusées': '#ef4444', 'Annulées': '#94a3b8' };
        const palette = ['#256747', '#2f7d57', '#4ade80', '#a7f3d0', '#86efac', '#14532d'];
        const euros = v => Number(v).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
        Chart.defaults.font.family = "'DM Sans', sans-serif";
        Chart.defaults.color = '#475569';
        Chart.defaults.maintainAspectRatio = false;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        const entiers = { beginAtZero: true, ticks: { precision: 0 } };
        const graphiques = [];
        const tracer = (id, config) => { const el = document.getElementById(id); if (el) graphiques.push(new Chart(el, config)); };

        tracer('graphique-evolution', {
            type: 'line',
            data: { labels: g.labels, datasets: Object.entries(g.evolution).map(([type, valeurs]) => ({
                label: type, data: valeurs, borderColor: couleurs[type], backgroundColor: couleurs[type] + '22', fill: true, tension: 0.35, pointRadius: 3,
            })) },
            options: { interaction: { mode: 'index', intersect: false }, scales: { y: entiers } },
        });
        tracer('graphique-inscriptions', {
            type: 'bar',
            data: { labels: g.labels, datasets: [{ label: 'Inscriptions', data: g.inscriptions, backgroundColor: '#2f7d57', borderRadius: 6 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: entiers } },
        });
        tracer('graphique-types', {
            type: 'doughnut',
            data: { labels: g.types.labels, datasets: [{ data: g.types.valeurs, backgroundColor: g.types.labels.map(t => couleurs[t]), borderWidth: 2 }] },
            options: { cutout: '62%', plugins: { legend: { position: 'bottom' } } },
        });
        tracer('graphique-statuts', {
            type: 'bar',
            data: { labels: g.statuts.labels, datasets: Object.keys(statuts).map(s => ({ label: s, data: g.statuts[s], backgroundColor: statuts[s], borderRadius: 4 })) },
            options: { indexAxis: 'y', scales: { x: { ...entiers, stacked: true }, y: { stacked: true } }, plugins: { legend: { position: 'bottom' } } },
        });
        tracer('graphique-chiffre', {
            type: 'bar',
            data: { labels: g.labels, datasets: [{ label: 'Chiffre d’affaires', data: g.chiffre, backgroundColor: '#256747', borderRadius: 6 }] },
            options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => euros(c.parsed.y) } } }, scales: { y: { beginAtZero: true, ticks: { callback: v => euros(v) } } } },
        });
        tracer('graphique-matieres', {
            type: 'bar',
            data: { labels: g.matieres.labels, datasets: [{ label: 'kg sauvés', data: g.matieres.valeurs, backgroundColor: palette, borderRadius: 6 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.parsed.x.toLocaleString('fr-FR') + ' kg' } } }, scales: { x: { beginAtZero: true, ticks: { callback: v => v + ' kg' } } } },
        });
        tracer('graphique-ateliers', {
            type: 'bar',
            data: { labels: g.ateliers.labels, datasets: [{ label: 'Demandes terminées', data: g.ateliers.valeurs, backgroundColor: '#7c3aed', borderRadius: 6 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: entiers } },
        });
        tracer('graphique-associations', {
            type: 'bar',
            data: { labels: g.associations.labels, datasets: [{ label: 'Dons remis', data: g.associations.valeurs, backgroundColor: '#e11d48', borderRadius: 6 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: entiers } },
        });

        // Impression / PDF : les graphiques se redimensionnent à la largeur de la page.
        window.addEventListener('beforeprint', () => graphiques.forEach(c => c.resize()));
        window.addEventListener('afterprint', () => graphiques.forEach(c => c.resize()));
    })();
</script>
@endpush
