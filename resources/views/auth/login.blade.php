<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — MAD Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 flex items-center justify-center">
    <div class="w-full max-w-sm">

        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">MAD Tracker</h1>
            <p class="text-sm text-slate-500 mt-1">KEHITAA SARL — Suivi des mises à disposition</p>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="email">Adresse email</label>
                        <input id="email" name="email" type="email" autocomplete="email"
                               value="{{ old('email') }}"
                               class="form-input @error('email') border-red-400 @enderror"
                               placeholder="vous@kehitaa.com">
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Mot de passe</label>
                        <input id="password" name="password" type="password" autocomplete="current-password"
                               class="form-input @error('password') border-red-400 @enderror"
                               placeholder="••••••••">
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600">
                            <span class="text-sm text-slate-600">Se souvenir</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center btn-lg">
                        Se connecter
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">© {{ date('Y') }} KEHITAA SARL</p>
    </div>
</body>
</html>
