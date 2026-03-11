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
        </div>
    </div>

    {{-- KPI globaux --}}
    <div class="grid grid-cols-3 md:grid-cols-6 gap-4">
        @php
            $kpis = [
                ['label' => 'Dossiers', 'value' => $statsGlobales['total'], 'color' => 'blue'],
                ['label' => 'Finalisés', 'value' => $statsGlobales['finalises'], 'color' => 'green'],
                ['label' => 'Taux finalisation', 'value' => $statsGlobales['taux_finalisation'].'%', 'color' => 'purple'],
                ['label' => 'Moy. traitement', 'value' => $statsGlobales['moy_traitement'].'j', 'color' => 'orange'],
                ['label' => 'Écart moy. MAD', 'value' => $statsGlobales['ecart_moyen_mad'].'j', 'color' => ($statsGlobales['ecart_moyen_mad'] > 0 ? 'red' : 'green')],
                ['label' => 'POD reçues', 'value' => $statsGlobales['total_pod'], 'color' => 'teal'],
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
                            <td class="text-right">
                                <span class="text-emerald-600 font-medium">{{ $f->finalises }}</span>
                            </td>
                            <td class="text-right text-xs @if(($f->moy_traitement ?? 0) > 30) text-red-600 font-semibold @endif">
                                {{ $f->moy_traitement ? round($f->moy_traitement, 1).'j' : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Par client --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Par client</h3>
            </div>
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
        </div>

        {{-- Par incoterm --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Par Incoterm</h3>
            </div>
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
        </div>

        {{-- Par responsable --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Par responsable</h3>
            </div>
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
        </div>

    </div>
</div>
