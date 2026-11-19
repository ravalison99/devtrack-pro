<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Tableau de bord') - DevTrack Pro</title>

    {{-- Thème appliqué avant le premier affichage : pas de flash blanc en mode sombre. --}}
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('dtp-theme');
                if (!theme) { theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.setAttribute('data-bs-theme', theme);
            } catch (e) { /* thème clair par défaut */ }
        })();
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/devtrack.css') }}?v={{ @filemtime(public_path('css/devtrack.css')) }}">

    @stack('styles')
</head>
<body class="{{ auth()->user()?->isStagiaire() ? 'avec-nav-basse' : '' }}">
    <a href="#contenu" class="skip-link">Aller au contenu</a>

    @include('partials.nav')

    @if (session('success'))
        <div class="toast-container-dtp" aria-live="polite" aria-atomic="true">
            <div class="toast toast-dtp align-items-center" role="status" data-auto>
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-check-circle-fill text-success me-2"></i>{{ session('success') }}</div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
                </div>
            </div>
        </div>
    @endif

    <main id="contenu" class="container my-4">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i>
                @if ($errors->count() === 1)
                    {{ $errors->first() }}
                @else
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $erreur)
                            <li>{{ $erreur }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/js/bootstrap.bundle.min.js" integrity="sha512-HvOjJrdwNpDbkGJIG2ZNqDlVqMo77qbs4Me4cah0HoDrfhrbA+8SBlZn1KrvAQw7cILLPFJvdwIgphzQmMm+Pw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('js/devtrack.js') }}?v={{ @filemtime(public_path('js/devtrack.js')) }}"></script>
    @stack('scripts')
</body>
</html>
