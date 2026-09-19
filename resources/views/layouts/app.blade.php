<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tableau de bord') - DevTrack Pro</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <style>
        :root {
            --dtp-primary: #4f46e5;
            --dtp-primary-dark: #4338ca;
            --dtp-bg: #f4f6fb;
        }

        body {
            background-color: var(--dtp-bg);
            font-family: -apple-system, "Segoe UI", Roboto, system-ui, sans-serif;
        }

        .navbar-devtrack {
            background: linear-gradient(90deg, var(--dtp-primary), var(--dtp-primary-dark));
        }

        .navbar-devtrack .navbar-brand {
            font-weight: 700;
            letter-spacing: .02em;
        }

        .navbar-devtrack .nav-link {
            color: rgba(255, 255, 255, .82);
            font-weight: 500;
        }

        .navbar-devtrack .nav-link.active {
            color: #fff;
            position: relative;
        }

        .navbar-devtrack .nav-link:hover {
            color: #fff;
        }

        .card {
            border: none;
            border-radius: .9rem;
            box-shadow: 0 .25rem 1rem rgba(31, 41, 55, .06);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eef0f5;
            border-radius: .9rem .9rem 0 0 !important;
            font-weight: 600;
        }

        .table thead th {
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .05em;
            color: #6b7280;
            border-bottom-width: 1px;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--dtp-primary);
            border-color: var(--dtp-primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--dtp-primary-dark);
            border-color: var(--dtp-primary-dark);
        }

        .page-title {
            font-weight: 700;
            color: #1f2937;
        }

        .stat-card {
            border-radius: .9rem;
            color: #fff;
            padding: 1.25rem 1.5rem;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-card .stat-label {
            opacity: .85;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
    </style>

    @stack('styles')
</head>
<body>
    @include('partials.nav')

    <main class="container my-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/js/bootstrap.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    @stack('scripts')
</body>
</html>
