<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Yönetim Girişi — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|instrument-serif:400,400i&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
    <div class="login-shell">
        <aside class="login-brand">
            <div class="login-glow login-glow--one" aria-hidden="true"></div>
            <div class="login-glow login-glow--two" aria-hidden="true"></div>

            <svg class="login-map" viewBox="0 0 800 600" aria-hidden="true">
                <defs>
                    <linearGradient id="adminLineGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#5eead4" stop-opacity="0" />
                        <stop offset="50%" stop-color="#5eead4" stop-opacity="0.7" />
                        <stop offset="100%" stop-color="#5eead4" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <g class="login-map-lines">
                    <path d="M90 420 C180 360, 220 300, 310 280 S470 250, 540 180" stroke="url(#adminLineGrad)" />
                    <path d="M140 120 C240 160, 280 220, 310 280 S390 390, 520 450" stroke="url(#adminLineGrad)" />
                    <path d="M40 260 C130 240, 210 250, 310 280 S480 310, 650 290" stroke="url(#adminLineGrad)" />
                    <path d="M310 280 L420 140" stroke="url(#adminLineGrad)" />
                    <path d="M310 280 L620 380" stroke="url(#adminLineGrad)" />
                </g>
                <g class="login-map-nodes">
                    <circle class="node node-a" cx="90" cy="420" r="4" />
                    <circle class="node node-b" cx="140" cy="120" r="3.5" />
                    <circle class="node node-c" cx="310" cy="280" r="7" />
                    <circle class="node node-d" cx="420" cy="140" r="4" />
                    <circle class="node node-e" cx="540" cy="180" r="3.5" />
                    <circle class="node node-f" cx="520" cy="450" r="4" />
                    <circle class="node node-g" cx="620" cy="380" r="3.5" />
                    <circle class="node node-h" cx="650" cy="290" r="3" />
                </g>
                <circle class="login-map-ring" cx="310" cy="280" r="28" />
                <circle class="login-map-ring login-map-ring--slow" cx="310" cy="280" r="52" />
            </svg>

            <div class="login-brand-copy">
                <p class="login-eyebrow">Yönetim alanı</p>
                <h1 class="login-title">PortGuard</h1>
                <p class="login-lead">
                    Tüm kullanıcıları, taramaları ve aktivite loglarını tek ekrandan yönetin.
                </p>
            </div>
        </aside>

        <main class="login-panel">
            <div class="login-card">
                <header class="login-card-head">
                    <h2>Admin girişi</h2>
                    <p>Bu alan yalnızca yönetim hesapları içindir.</p>
                </header>

                @if ($errors->any())
                    <div class="login-alert" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.store') }}" class="login-form" autocomplete="on">
                    @csrf

                    <div class="login-field">
                        <label for="email">E-posta</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            inputmode="email"
                            spellcheck="false"
                            placeholder="admin@portguard.com.tr"
                        >
                    </div>

                    <div class="login-field">
                        <label for="password">Şifre</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Şifrenizi girin"
                        >
                    </div>

                    <label class="login-remember">
                        <input id="remember_me" type="checkbox" name="remember" value="1">
                        <span>Beni hatırla</span>
                    </label>

                    <button type="submit" class="login-submit">Giriş yap</button>
                </form>

                <p class="login-admin-back">
                    <a href="{{ route('login') }}">Kullanıcı paneline dön</a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
