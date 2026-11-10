<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e4d6b">
    <title>@yield('title', 'Connexion') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="auth-page">
        <div class="auth-card">
            <div class="auth-brand">
                <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                <div>
                    <div class="brand-name">{{ config('app.name') }}</div>
                    <div class="brand-sub">Suivi pédagogique</div>
                </div>
            </div>

            @yield('content')
        </div>
    </main>
</body>
</html>
