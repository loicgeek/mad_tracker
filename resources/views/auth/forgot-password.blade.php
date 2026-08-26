<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — MAD Tracker</title>
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
                <p class="text-sm text-slate-600 mb-5">Saisissez votre adresse email, nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>

                @if (session('status'))
                    <div class="alert-success mb-5">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

                    <button type="submit" class="btn-primary w-full justify-center btn-lg">
                        Envoyer le lien de réinitialisation
                    </button>
                </form>

                <p class="text-center text-sm mt-5">
                    <a href="{{ route('login') }}" class="text-brand-600 hover:underline">Retour à la connexion</a>
                </p>
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">© {{ date('Y') }} KEHITAA SARL</p>
    </div>
</body>
</html>
