<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Analyses & Performances</h2>
            <p class="text-sm text-slate-500">Temps de traitement, écarts, tendances</p>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-sm text-slate-600">Période :</label>
            <select wire:model.live="periode" class="form-select w-36">
                <option value="3">3 mois</option>
                <option value="6">6 mois</option>
                <option value="12">12 mois</option>
                <option value="24">24 mois</option>
            </select>

            {{-- Vue toggle --}}
            <div class="flex rounded-lg border border-slate-200 overflow-hidden text-sm">
                <button wire:click="$set('viewMode','table')"
                        class="px-3 py-1.5 flex items-center gap-1.5 transition-colors
                               {{ $viewMode === 'table' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                    </svg>
                    Tableau
                </button>
                <button wire:click="$set('viewMode','chart')"
                        class="px-3 py-1.5 flex items-center gap-1.5 transition-colors border-l border-slate-200
                               {{ $viewMode === 'chart' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Graphique
                </button>
            </div>

            {{-- Export CSV --}}
            <a href="{{ route('export.analyses', ['periode' => $periode]) }}"
               class="btn btn-secondary flex items-center gap-1.5 text-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exporter CSV
            </a>
        </div>
    </div>

    {{-- KPI globaux --}}
    <div class="grid grid-cols-3 md:grid-cols-6 gap-4">
        @php
            $kpis = [
                ['label' => 'Dossiers',         'value' => $statsGlobales['total'],                 'color' => 'blue'],
                ['label' => 'Finalisés',         'value' => $statsGlobales['finalises'],             'color' => 'green'],
                ['label' => 'Taux finalisation', 'value' => $statsGlobales['taux_finalisation'].'%', 'color' => 'purple'],
                ['label' => 'Moy. traitement',   'value' => $statsGlobales['moy_traitement'].'j',    'color' => 'orange'],
                ['label' => 'Écart moy. MAD',    'value' => $statsGlobales['ecart_moyen_mad'].'j',   'color' => ($statsGlobales['ecart_moyen_mad'] > 0 ? 'red' : 'green')],
                ['label' => 'POD reçues',        'value' => $statsGlobales['total_pod'],             'color' => 'teal'],
            ];
        @endphp
        @foreach($kpis as $kpi)
        <div class="card p-4 text-center">
            <p class="text-xs text-slate-500 mb-1">{{ $kpi['label'] }}</p>
            <p class="text-2xl font-bold text-slate-900">{{ $kpi['value'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-2 gap-6">

        {{-- Par fournisseur --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Par fournisseur</h3>
            </div>
            @if($viewMode === 'table')
            <div class="table-wrapper rounded-none rounded-b-xl border-0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fournisseur</th>
                            <th class="text-right">Dossiers</th>
                            <th class="text-right">Finalisés</th>
                            <th class="text-right">Moy. (j)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parFournisseur as $f)
                        <tr>
                            <td class="font-medium text-sm">{{ $f->nom }}</td>
                            <td class="text-right">{{ $f->total }}</td>
                            <td class="text-right"><span class="text-emerald-600 font-medium">{{ $f->finalises }}</span></td>
                            <td class="text-right text-xs @if(($f->moy_traitement ?? 0) > 30) text-red-600 font-semibold @endif">
                                {{ $f->moy_traitement ? round($f->moy_traitement, 1).'j' : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            @php
                $fNoms    = collect($parFournisseur)->pluck('nom')->values()->toArray();
                $fTotal   = collect($parFournisseur)->pluck('total')->values()->toArray();
                $fFinal   = collect($parFournisseur)->pluck('finalises')->values()->toArray();
            @endphp
            <div wire:key="chart-fournisseur-{{ $periode }}"
                 x-data="{
                     init() {
                         new ApexCharts(this.$refs.chart, {
                             chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                             plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '60%' } },
                             series: [
                                 { name: 'Total',     data: {{ json_encode($fTotal) }} },
                                 { name: 'Finalisés', data: {{ json_encode($fFinal) }} },
                             ],
                             xaxis: { categories: {{ json_encode($fNoms) }} },
                             colors: ['#6366f1','#22c55e'],
                             legend: { position: 'top' },
                             dataLabels: { enabled: false },
                             grid: { borderColor: '#f1f5f9' },
                         }).render();
                     }
                 }" class="p-4">
                <div x-ref="chart" style="min-height:280px"></div>
            </div>
            @endif
        </div>

        {{-- Par client --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Par client</h3>
            </div>
            @if($viewMode === 'table')
            <div class="table-wrapper rounded-none rounded-b-xl border-0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th class="text-right">Dossiers</th>
                            <th class="text-right">Finalisés</th>
                            <th class="text-right">Taux</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parClient as $c)
                        <tr>
                            <td class="font-medium text-sm">{{ $c->nom }}</td>
                            <td class="text-right">{{ $c->total }}</td>
                            <td class="text-right text-emerald-600 font-medium">{{ $c->finalises }}</td>
                            <td class="text-right text-xs">
                                {{ $c->total > 0 ? round($c->finalises / $c->total * 100).'%' : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            @php
                $cNoms  = collect($parClient)->pluck('nom')->values()->toArray();
                $cTotal = collect($parClient)->pluck('total')->values()->toArray();
                $cFinal = collect($parClient)->pluck('finalises')->values()->toArray();
                $cTaux  = collect($parClient)->map(fn($c) => $c->total > 0 ? round($c->finalises / $c->total * 100, 1) : 0)->values()->toArray();
            @endphp
            <div wire:key="chart-client-{{ $periode }}"
                 x-data="{
                     init() {
                         new ApexCharts(this.$refs.chart, {
                             chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                             plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '60%' } },
                             series: [
                                 { name: 'Total',     data: {{ json_encode($cTotal) }} },
                                 { name: 'Finalisés', data: {{ json_encode($cFinal) }} },
                             ],
                             xaxis: { categories: {{ json_encode($cNoms) }} },
                             colors: ['#3b82f6','#22c55e'],
                             legend: { position: 'top' },
                             dataLabels: { enabled: false },
                             grid: { borderColor: '#f1f5f9' },
                         }).render();
                     }
                 }" class="p-4">
                <div x-ref="chart" style="min-height:280px"></div>
            </div>
            @endif
        </div>

        {{-- Par incoterm --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Par Incoterm</h3>
            </div>
            @if($viewMode === 'table')
            <div class="table-wrapper rounded-none rounded-b-xl border-0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Incoterm</th>
                            <th class="text-right">Dossiers</th>
                            <th class="text-right">Moy. traitement (j)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parIncoterm as $i)
                        <tr>
                            <td class="font-medium text-sm">{{ $i->incoterm }}</td>
                            <td class="text-right">{{ $i->total }}</td>
                            <td class="text-right text-xs">{{ $i->moy_traitement ? round($i->moy_traitement, 1).'j' : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            @php
                $iLabels = collect($parIncoterm)->pluck('incoterm')->values()->toArray();
                $iTotal  = collect($parIncoterm)->pluck('total')->values()->toArray();
                $iMoy    = collect($parIncoterm)->map(fn($i) => $i->moy_traitement ? round($i->moy_traitement, 1) : 0)->values()->toArray();
            @endphp
            <div wire:key="chart-incoterm-{{ $periode }}"
                 x-data="{
                     init() {
                         new ApexCharts(this.$refs.chart, {
                             chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                             plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                             series: [
                                 { name: 'Dossiers',       data: {{ json_encode($iTotal) }} },
                                 { name: 'Moy. trt. (j)', data: {{ json_encode($iMoy) }} },
                             ],
                             xaxis: { categories: {{ json_encode($iLabels) }} },
                             colors: ['#6366f1','#f59e0b'],
                             legend: { position: 'top' },
                             dataLabels: { enabled: false },
                             grid: { borderColor: '#f1f5f9' },
                         }).render();
                     }
                 }" class="p-4">
                <div x-ref="chart" style="min-height:260px"></div>
            </div>
            @endif
        </div>

        {{-- Par responsable --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Par responsable</h3>
            </div>
            @if($viewMode === 'table')
            <div class="table-wrapper rounded-none rounded-b-xl border-0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Responsable</th>
                            <th class="text-right">Dossiers</th>
                            <th class="text-right">Finalisés</th>
                            <th class="text-right">Alertes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parResponsable as $r)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-gray">{{ $r->initiales }}</span>
                                    <span class="text-sm font-medium">{{ $r->nom }}</span>
                                </div>
                            </td>
                            <td class="text-right">{{ $r->total }}</td>
                            <td class="text-right text-emerald-600 font-medium">{{ $r->finalises }}</td>
                            <td class="text-right">
                                @if($r->alertes > 0)
                                    <span class="badge badge-red">{{ $r->alertes }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            @php
                $rNoms    = collect($parResponsable)->pluck('initiales')->values()->toArray();
                $rTotal   = collect($parResponsable)->pluck('total')->values()->toArray();
                $rFinal   = collect($parResponsable)->pluck('finalises')->values()->toArray();
                $rAlertes = collect($parResponsable)->pluck('alertes')->values()->toArray();
            @endphp
            <div wire:key="chart-responsable-{{ $periode }}"
                 x-data="{
                     init() {
                         new ApexCharts(this.$refs.chart, {
                             chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif', stacked: false },
                             plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
                             series: [
                                 { name: 'Total',     data: {{ json_encode($rTotal) }} },
                                 { name: 'Finalisés', data: {{ json_encode($rFinal) }} },
                                 { name: 'Alertes',   data: {{ json_encode($rAlertes) }} },
                             ],
                             xaxis: { categories: {{ json_encode($rNoms) }} },
                             colors: ['#6366f1','#22c55e','#ef4444'],
                             legend: { position: 'top' },
                             dataLabels: { enabled: false },
                             grid: { borderColor: '#f1f5f9' },
                         }).render();
                     }
                 }" class="p-4">
                <div x-ref="chart" style="min-height:260px"></div>
            </div>
            @endif
        </div>

    </div>

    {{-- ── Performance par transporteur ──────────────────────────── --}}
    @if(count($parTransporteur) > 0)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">Performance par transporteur</h3>
        </div>
        @if($viewMode === 'table')
        <div class="table-wrapper rounded-none rounded-b-xl border-0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Transporteur</th>
                        <th class="text-right">Dossiers</th>
                        <th class="text-right">% à temps</th>
                        <th class="text-right">Coût prévu moy.</th>
                        <th class="text-right">Coût réel moy.</th>
                        <th class="text-right">Écart %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parTransporteur as $r)
                    <tr>
                        <td class="font-medium text-slate-800">{{ $r->nom }}</td>
                        <td class="text-right">{{ $r->total }}</td>
                        <td class="text-right">
                            @if($r->pct_a_temps !== null)
                                <span class="font-semibold {{ $r->pct_a_temps >= 80 ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ $r->pct_a_temps }}%
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-right text-xs">{{ $r->moy_cout_prevu ? number_format($r->moy_cout_prevu, 0).' €' : '—' }}</td>
                        <td class="text-right text-xs">{{ $r->moy_cout_reel ? number_format($r->moy_cout_reel, 0).' €' : '—' }}</td>
                        <td class="text-right">
                            @if($r->ecart_pct !== null)
                                <span class="font-semibold {{ $r->ecart_pct > 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                    {{ $r->ecart_pct > 0 ? '+' : '' }}{{ $r->ecart_pct }}%
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        @php
            $tNoms      = collect($parTransporteur)->pluck('nom')->values()->toArray();
            $tPctTemps  = collect($parTransporteur)->map(fn($r) => $r->pct_a_temps ?? 0)->values()->toArray();
            $tEcartPct  = collect($parTransporteur)->map(fn($r) => $r->ecart_pct ?? 0)->values()->toArray();
            $tCoutPrevu = collect($parTransporteur)->map(fn($r) => $r->moy_cout_prevu ? round($r->moy_cout_prevu) : 0)->values()->toArray();
            $tCoutReel  = collect($parTransporteur)->map(fn($r) => $r->moy_cout_reel ? round($r->moy_cout_reel) : 0)->values()->toArray();
        @endphp
        <div wire:key="chart-transporteur-{{ $periode }}"
             x-data="{
                 init() {
                     // % à temps
                     new ApexCharts(this.$refs.chartTemps, {
                         chart: { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                         plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '55%',
                             dataLabels: { position: 'top' } } },
                         series: [{ name: '% à temps', data: {{ json_encode($tPctTemps) }} }],
                         xaxis: { categories: {{ json_encode($tNoms) }}, max: 100,
                             labels: { formatter: v => v + '%' } },
                         colors: ['#22c55e'],
                         dataLabels: { enabled: true, formatter: v => v + '%', offsetX: 20,
                             style: { fontSize: '11px', colors: ['#475569'] } },
                         grid: { borderColor: '#f1f5f9' },
                         title: { text: '% livraisons à temps', style: { fontSize: '12px', color: '#64748b', fontWeight: 500 } },
                     }).render();
                     // Coûts prévu vs réel
                     new ApexCharts(this.$refs.chartCouts, {
                         chart: { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                         plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
                         series: [
                             { name: 'Coût prévu moy.', data: {{ json_encode($tCoutPrevu) }} },
                             { name: 'Coût réel moy.',  data: {{ json_encode($tCoutReel) }} },
                         ],
                         xaxis: { categories: {{ json_encode($tNoms) }} },
                         colors: ['#6366f1','#f59e0b'],
                         legend: { position: 'top' },
                         dataLabels: { enabled: false },
                         yaxis: { labels: { formatter: v => v.toFixed(0) + ' €' } },
                         grid: { borderColor: '#f1f5f9' },
                         title: { text: 'Coût prévu vs réel (moy.)', style: { fontSize: '12px', color: '#64748b', fontWeight: 500 } },
                     }).render();
                 }
             }" class="p-4 grid grid-cols-2 gap-6">
            <div x-ref="chartTemps"  style="min-height:220px"></div>
            <div x-ref="chartCouts"  style="min-height:220px"></div>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Standard vs Projet + Délai par Incoterm ────────────────── --}}
    <div class="grid grid-cols-2 gap-6">

        @if(count($parType) > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Standard vs Projet</h3>
            </div>
            @if($viewMode === 'table')
            <div class="table-wrapper rounded-none rounded-b-xl border-0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Finalisés</th>
                            <th class="text-right">Taux</th>
                            <th class="text-right">Délai moy.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parType as $r)
                        <tr>
                            <td>
                                <span class="badge {{ $r->type_commande === 'projet' ? 'badge-purple' : 'badge-gray' }} capitalize">
                                    {{ $r->type_commande }}
                                </span>
                            </td>
                            <td class="text-right">{{ $r->total }}</td>
                            <td class="text-right text-emerald-600 font-medium">{{ $r->finalises }}</td>
                            <td class="text-right">{{ $r->taux }}%</td>
                            <td class="text-right text-xs">
                                @if($r->delai_moyen !== null)
                                    <span class="{{ $r->delai_moyen > 0 ? 'text-red-500' : 'text-emerald-600' }} font-medium">
                                        {{ $r->delai_moyen > 0 ? '+' : '' }}{{ $r->delai_moyen }}j
                                    </span>
                                @else —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            @php
                $typeLabels = collect($parType)->map(fn($r) => ucfirst($r->type_commande))->values()->toArray();
                $typeTotal  = collect($parType)->pluck('total')->values()->toArray();
                $typeFinal  = collect($parType)->pluck('finalises')->values()->toArray();
                $typeTaux   = collect($parType)->pluck('taux')->values()->toArray();
            @endphp
            <div wire:key="chart-type-{{ $periode }}"
                 x-data="{
                     init() {
                         new ApexCharts(this.$refs.chart, {
                             chart: { type: 'donut', height: 260, fontFamily: 'Instrument Sans, sans-serif' },
                             series: {{ json_encode($typeTotal) }},
                             labels: {{ json_encode($typeLabels) }},
                             colors: ['#6366f1','#a855f7'],
                             legend: { position: 'bottom' },
                             plotOptions: { pie: { donut: { size: '60%',
                                 labels: { show: true, total: { show: true, label: 'Total', fontSize: '14px' } }
                             }}},
                             dataLabels: { formatter: (v, opts) =>
                                 opts.w.config.labels[opts.seriesIndex] + ': ' + opts.w.globals.series[opts.seriesIndex]
                             },
                         }).render();
                     }
                 }" class="p-4">
                <div x-ref="chart" style="min-height:260px"></div>
            </div>
            @endif
        </div>
        @endif

        @if(count($delaiParIncoterm) > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Délai moyen de livraison par incoterm</h3>
            </div>
            @if($viewMode === 'table')
            <div class="table-wrapper rounded-none rounded-b-xl border-0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Incoterm</th>
                            <th class="text-right">Dossiers livrés</th>
                            <th class="text-right">Délai moyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($delaiParIncoterm as $r)
                        <tr>
                            <td class="font-medium">{{ $r->incoterm }}</td>
                            <td class="text-right">{{ $r->total }}</td>
                            <td class="text-right">
                                <span class="{{ $r->delai_moyen > 0 ? 'text-red-500' : 'text-emerald-600' }} font-semibold">
                                    {{ $r->delai_moyen > 0 ? '+' : '' }}{{ $r->delai_moyen }}j
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            @php
                $diLabels = collect($delaiParIncoterm)->pluck('incoterm')->values()->toArray();
                $diDelais = collect($delaiParIncoterm)->pluck('delai_moyen')->values()->toArray();
                $diColors = collect($delaiParIncoterm)->map(fn($r) => $r->delai_moyen > 0 ? '#ef4444' : '#22c55e')->values()->toArray();
            @endphp
            <div wire:key="chart-delai-incoterm-{{ $periode }}"
                 x-data="{
                     init() {
                         new ApexCharts(this.$refs.chart, {
                             chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                             plotOptions: { bar: { borderRadius: 4, columnWidth: '55%',
                                 distributed: true } },
                             series: [{ name: 'Délai moyen (j)', data: {{ json_encode($diDelais) }} }],
                             xaxis: { categories: {{ json_encode($diLabels) }} },
                             colors: {{ json_encode($diColors) }},
                             legend: { show: false },
                             dataLabels: { enabled: true,
                                 formatter: v => (v > 0 ? '+' : '') + v + 'j',
                                 style: { fontSize: '11px', colors: ['#475569'] } },
                             yaxis: { labels: { formatter: v => (v > 0 ? '+' : '') + v + 'j' } },
                             grid: { borderColor: '#f1f5f9' },
                         }).render();
                     }
                 }" class="p-4">
                <div x-ref="chart" style="min-height:260px"></div>
            </div>
            @endif
        </div>
        @endif

    </div>

    {{-- ── Délais de validation des documents ────────────────────── --}}
    @if(count($delaiValidationDocs) > 0)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">Délais de validation des documents</h3>
            <p class="text-xs text-slate-500 mt-0.5">Écart entre la date MAD réelle et la date de validation des documents.</p>
        </div>
        @if($viewMode === 'table')
        <div class="table-wrapper rounded-none rounded-b-xl border-0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Type</th>
                        <th class="text-right">MAD réelle</th>
                        <th class="text-right">Date validation</th>
                        <th class="text-right">Écart (j)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($delaiValidationDocs as $r)
                    <tr>
                        <td class="font-mono text-xs font-medium text-slate-700">{{ $r->reference }}</td>
                        <td>
                            <span class="badge {{ $r->type_commande === 'projet' ? 'badge-purple' : 'badge-gray' }} capitalize text-xs">
                                {{ $r->type_commande ?? '—' }}
                            </span>
                        </td>
                        <td class="text-right text-xs text-slate-600">{{ $r->mad_reelle ? \Carbon\Carbon::parse($r->mad_reelle)->format('d/m/Y') : '—' }}</td>
                        <td class="text-right text-xs text-slate-600">{{ $r->date_validation ? \Carbon\Carbon::parse($r->date_validation)->format('d/m/Y') : '—' }}</td>
                        <td class="text-right">
                            @if($r->ecart_jours !== null)
                                <span class="font-semibold {{ $r->ecart_jours > 7 ? 'text-red-500' : ($r->ecart_jours > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                                    {{ $r->ecart_jours > 0 ? '+' : '' }}{{ $r->ecart_jours }}j
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="4" class="text-right text-xs font-semibold text-slate-600 py-2 px-4">Écart moyen</td>
                        <td class="text-right text-xs font-bold py-2 px-4">
                            @php $moyValid = collect($delaiValidationDocs)->avg('ecart_jours'); @endphp
                            <span class="{{ $moyValid > 7 ? 'text-red-500' : 'text-slate-700' }}">
                                {{ $moyValid !== null ? (round($moyValid, 1) > 0 ? '+' : '') . round($moyValid, 1) . 'j' : '—' }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        @php
            $dvRefs   = collect($delaiValidationDocs)->pluck('reference')->values()->toArray();
            $dvEcarts = collect($delaiValidationDocs)->pluck('ecart_jours')->map(fn($v) => (int)$v)->values()->toArray();
            $dvColors = collect($dvEcarts)->map(fn($v) => $v > 7 ? '#ef4444' : ($v > 0 ? '#f59e0b' : '#22c55e'))->values()->toArray();
        @endphp
        <div wire:key="chart-validation-docs-{{ $periode }}"
             x-data="{
                 init() {
                     new ApexCharts(this.$refs.chart, {
                         chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                         plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '60%', distributed: true } },
                         series: [{ name: 'Écart (j)', data: {{ json_encode($dvEcarts) }} }],
                         xaxis: { categories: {{ json_encode($dvRefs) }}, labels: { formatter: v => (v > 0 ? '+' : '') + v + 'j' } },
                         colors: {{ json_encode($dvColors) }},
                         legend: { show: false },
                         dataLabels: { enabled: true, formatter: v => (v > 0 ? '+' : '') + v + 'j',
                             offsetX: 20, style: { fontSize: '11px', colors: ['#475569'] } },
                         grid: { borderColor: '#f1f5f9' },
                     }).render();
                 }
             }" class="p-4">
            <div x-ref="chart" style="min-height:300px"></div>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Échéancier des paiements ────────────────────────────────── --}}
    @if(count($echeancierPaiements) > 0)
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800">Échéancier des paiements</h3>
            <p class="text-xs text-slate-500 mt-0.5">Suivi des factures et respect des délais de paiement.</p>
        </div>
        <div class="table-wrapper rounded-none rounded-b-xl border-0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Dossier</th>
                        <th>Client</th>
                        <th>N° Facture</th>
                        <th class="text-right">Montant</th>
                        <th class="text-right">Date facture</th>
                        <th class="text-right">Échéance</th>
                        <th class="text-right">Date paiement</th>
                        <th class="text-right">Statut</th>
                        <th class="text-right">Écart</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($echeancierPaiements as $r)
                    <tr>
                        <td class="font-mono text-xs font-medium text-slate-700">{{ $r->reference }}</td>
                        <td class="text-sm text-slate-700">{{ $r->client }}</td>
                        <td class="text-xs text-slate-600">{{ $r->numero_facture ?? '—' }}</td>
                        <td class="text-right text-xs font-medium">
                            {{ $r->montant ? number_format($r->montant, 0, ',', ' ').' '.($r->devise ?? 'EUR') : '—' }}
                        </td>
                        <td class="text-right text-xs text-slate-600">{{ $r->date_facturation ? \Carbon\Carbon::parse($r->date_facturation)->format('d/m/Y') : '—' }}</td>
                        <td class="text-right text-xs {{ (!$r->paiement_recu && \Carbon\Carbon::parse($r->date_echeance)->isPast()) ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                            {{ \Carbon\Carbon::parse($r->date_echeance)->format('d/m/Y') }}
                        </td>
                        <td class="text-right text-xs text-slate-600">
                            {{ $r->date_paiement ? \Carbon\Carbon::parse($r->date_paiement)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="text-right">
                            @if($r->paiement_recu)
                                <span class="badge badge-emerald">Payé</span>
                            @elseif(\Carbon\Carbon::parse($r->date_echeance)->isPast())
                                <span class="badge badge-red">En retard</span>
                            @else
                                <span class="badge badge-gray">En attente</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($r->ecart_echeance_jours !== null)
                                @php $ecart = (int)$r->ecart_echeance_jours; @endphp
                                <span class="font-semibold text-xs {{ $ecart > 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                    {{ $ecart > 0 ? '+' : '' }}{{ $ecart }}j
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @php
                    $totalNonPaye = collect($echeancierPaiements)->where('paiement_recu', 0)->sum('montant');
                    $totalEnRetard = collect($echeancierPaiements)->filter(fn($r) => !$r->paiement_recu && \Carbon\Carbon::parse($r->date_echeance)->isPast())->sum('montant');
                @endphp
                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="3" class="text-right text-xs font-semibold text-slate-600 py-2 px-4">
                            Total non payé
                        </td>
                        <td class="text-right text-xs font-bold py-2 px-4 text-amber-600">
                            {{ $totalNonPaye > 0 ? number_format($totalNonPaye, 0, ',', ' ').' €' : '—' }}
                        </td>
                        <td colspan="4" class="text-right text-xs font-semibold text-slate-600 py-2 px-4">
                            Dont en retard
                        </td>
                        <td class="text-right text-xs font-bold py-2 px-4 text-red-500">
                            {{ $totalEnRetard > 0 ? number_format($totalEnRetard, 0, ',', ' ').' €' : '—' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

</div>
