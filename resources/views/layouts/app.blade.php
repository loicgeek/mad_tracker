<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MAD Tracker' }} — KEHITAA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full flex bg-slate-50">

    {{-- ── Sidebar ─────────────────────────────────────────────── --}}
    <aside class="w-64 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col h-screen sticky top-0">

        {{-- Logo --}}
        <div class="px-5 py-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900 leading-tight">MAD Tracker</p>
                    <p class="text-xs text-slate-400">KEHITAA SARL</p>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <a href="{{ route('dashboard') }}"
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Tableau de bord
            </a>

            <a href="{{ route('dossiers.index') }}"
               class="sidebar-link {{ request()->routeIs('dossiers.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Dossiers
            </a>

            <a href="{{ route('analyses') }}"
               class="sidebar-link {{ request()->routeIs('analyses') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analyses
            </a>

            <div class="pt-3">
                <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Référentiels</p>

                <a href="{{ route('clients.index') }}"
                   class="sidebar-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Clients
                </a>

                <a href="{{ route('fournisseurs.index') }}"
                   class="sidebar-link {{ request()->routeIs('fournisseurs.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Fournisseurs
                </a>
            </div>

            <div class="pt-3">
                <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Actions</p>
                <a href="{{ route('dossiers.create') }}"
                   class="sidebar-link text-brand-600 hover:text-brand-700 hover:bg-brand-50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouveau dossier
                </a>
                <a href="{{ route('import') }}"
                   class="sidebar-link {{ request()->routeIs('import*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Importer données
                </a>
                <a href="{{ route('export.dossiers') }}"
                   class="sidebar-link">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exporter Excel
                </a>
            </div>
        </nav>

        {{-- User --}}
        <div class="px-3 py-4 border-t border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->prenom ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()->nom ?? '', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 truncate">{{ auth()->user()->nom_complet ?? 'Utilisateur' }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ auth()->user()->role ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-icon" title="Déconnexion">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ── Main content ────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">

        {{-- Top bar --}}
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-20">
            <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'MAD Tracker' }}</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-400">{{ now()->format('d/m/Y') }}</span>
            </div>
        </header>

        {{-- Flash notifications --}}
        <div
            x-data="{ notifications: [] }"
            @notify.window="
                const id = Date.now();
                notifications.push({ id, ...($event.detail[0] ?? $event.detail) });
                setTimeout(() => notifications = notifications.filter(n => n.id !== id), 4000);
            "
            class="fixed bottom-6 right-6 z-50 space-y-2"
        >
            <template x-for="n in notifications" :key="n.id">
                <div x-show="true"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-end="opacity-0"
                     :class="{
                         'bg-emerald-600': n.type === 'success',
                         'bg-red-600': n.type === 'error',
                         'bg-amber-500': n.type === 'warning',
                         'bg-brand-600': n.type === 'info',
                     }"
                     class="text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-3 text-sm font-medium min-w-64">
                    <span x-text="n.message"></span>
                </div>
            </template>
        </div>

        {{-- Page content --}}
        <main class="flex-1 p-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
