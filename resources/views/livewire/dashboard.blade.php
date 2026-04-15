<div class="space-y-8">

    {{-- ── Stats Row ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">

        <div class="stat-card">
            <div>
                <p class="stat-label">Total dossiers</p>
                <p class="stat-value">{{ $stats['total'] }}</p>
            </div>
            <div class="stat-icon bg-brand-50">
                <svg class="w-5 h-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <p class="stat-label">En cours</p>
                <p class="stat-value text-brand-700">{{ $stats['en_cours'] }}</p>
            </div>
            <div class="stat-icon bg-blue-50">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <p class="stat-label">Avec alertes</p>
                <p class="stat-value text-red-600">{{ $stats['avec_alertes'] }}</p>
            </div>
            <div class="stat-icon bg-red-50">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <p class="stat-label">Finalisés</p>
                <p class="stat-value text-emerald-600">{{ $stats['finalises'] }}</p>
            </div>
            <div class="stat-icon bg-emerald-50">
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

    </div>

    {{-- ── KPI Row ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-5">
        <div class="card p-5 text-center">
            <p class="text-sm text-slate-500 mb-1">Moy. temps de traitement</p>
            <p class="text-3xl font-bold text-slate-900">{{ $stats['moy_traitement'] }}<span class="text-base text-slate-400 ml-1">j</span></p>
        </div>
        <div class="card p-5 text-center">
            <p class="text-sm text-slate-500 mb-1">POD en attente</p>
            <p class="text-3xl font-bold text-amber-600">{{ $stats['pod_en_attente'] }}</p>
        </div>
        <div class="card p-5 text-center">
            <p class="text-sm text-slate-500 mb-1">Fournisseurs actifs</p>
            <p class="text-3xl font-bold text-slate-900">{{ $stats['fournisseurs'] }}</p>
        </div>
    </div>

    {{-- ── KPI Livraisons & Coûts ───────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="card p-4 text-center">
            <p class="text-xs text-slate-500 mb-1">Livraisons à temps</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['taux_livraison_temps'] }}<span class="text-sm ml-0.5">%</span></p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-slate-500 mb-1">Taux de retard</p>
            <p class="text-2xl font-bold text-red-500">{{ $stats['taux_retard'] }}<span class="text-sm ml-0.5">%</span></p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-slate-500 mb-1">Délai moyen livraison</p>
            <p class="text-2xl font-bold {{ $stats['delai_moyen_livraison'] > 0 ? 'text-red-500' : 'text-emerald-600' }}">
                {{ $stats['delai_moyen_livraison'] }}<span class="text-sm text-slate-400 ml-1">j</span>
            </p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-slate-500 mb-1">Écart coût total</p>
            <p class="text-2xl font-bold {{ $stats['ecart_cout_total'] > 0 ? 'text-red-500' : ($stats['ecart_cout_total'] < 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                {{ $stats['ecart_cout_total'] > 0 ? '+' : '' }}{{ number_format($stats['ecart_cout_total'], 0) }}<span class="text-sm ml-0.5">€</span>
            </p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-slate-500 mb-1">Écart coût moyen</p>
            <p class="text-2xl font-bold {{ $stats['ecart_cout_pct'] > 0 ? 'text-red-500' : ($stats['ecart_cout_pct'] < 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                {{ $stats['ecart_cout_pct'] > 0 ? '+' : '' }}{{ $stats['ecart_cout_pct'] }}<span class="text-sm ml-0.5">%</span>
            </p>
        </div>
    </div>

    {{-- ── Charts ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-6">

        {{-- Chart statuts --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Répartition par statut</h3>
            </div>
            <div class="card-body">
                <div id="chart-statuts" style="min-height:260px"></div>
            </div>
        </div>

        {{-- Chart mensuel --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Dossiers par mois</h3>
            </div>
            <div class="card-body">
                <div id="chart-mensuel" style="min-height:260px"></div>
            </div>
        </div>

    </div>

    {{-- Chart coûts prévu vs réel --}}
    @if(count($chartCouts['labels']) > 0)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">Coût transport prévu vs réel (moy. mensuelle)</h3>
        </div>
        <div class="card-body">
            <div id="chart-couts" style="min-height:260px"></div>
        </div>
    </div>
    @endif

    {{-- ── Alertes actives ─────────────────────────────────────── --}}
    @if(count($alertes) > 0)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">⚠️ Dossiers avec alertes</h3>
            <a href="{{ route('dossiers.index', ['filterAlertes' => 'oui']) }}" class="text-sm text-brand-600 hover:underline">
                Voir tous
            </a>
        </div>
        <div class="table-wrapper rounded-none rounded-b-xl border-0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Statut</th>
                        <th>Alertes actives</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alertes as $a)
                    <tr>
                        <td class="font-mono text-xs font-medium text-slate-700">{{ $a['reference'] }}</td>
                        <td>{{ $a['client'] }}</td>
                        <td>
                            <span class="badge badge-{{ $a['color'] === 'green' ? 'green' : ($a['color'] === 'red' ? 'red' : 'blue') }}">
                                {{ $a['statut'] }}
                            </span>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($a['alertes'] as $alerte)
                                    <span class="badge badge-red text-xs">{{ $alerte }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('dossiers.show', $a['id']) }}" class="btn btn-ghost btn-sm">Voir →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Statuts chart
    const statuts = @json($chartStatuts);
    new ApexCharts(document.getElementById('chart-statuts'), {
        chart: { type: 'donut', height: 260, fontFamily: 'Instrument Sans, sans-serif' },
        series: statuts.data,
        labels: statuts.labels,
        colors: ['#94a3b8','#3b82f6','#a855f7','#eab308','#f97316','#14b8a6','#22c55e'],
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
    }).render();

    // Mensuel chart
    const monthly = @json($chartMensuel);
    new ApexCharts(document.getElementById('chart-mensuel'), {
        chart: { type: 'bar', height: 260, fontFamily: 'Instrument Sans, sans-serif', toolbar: { show: false } },
        series: [{ name: 'Dossiers', data: monthly.data }],
        xaxis: { categories: monthly.labels },
        colors: ['#2952ff'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f5f9' },
    }).render();

    // Coûts chart
    const coutsEl = document.getElementById('chart-couts');
    if (coutsEl) {
        const couts = @json($chartCouts);
        new ApexCharts(coutsEl, {
            chart: { type: 'bar', height: 260, fontFamily: 'Instrument Sans, sans-serif', toolbar: { show: false } },
            series: [
                { name: 'Coût prévu (moy.)', data: couts.prevu },
                { name: 'Coût réel (moy.)',  data: couts.reel  },
            ],
            xaxis: { categories: couts.labels },
            colors: ['#6366f1', '#f59e0b'],
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 3 } },
            dataLabels: { enabled: false },
            yaxis: { labels: { formatter: v => v.toFixed(0) + ' €' } },
            legend: { position: 'top' },
            grid: { borderColor: '#f1f5f9' },
        }).render();
    }
});
</script>
@endpush
