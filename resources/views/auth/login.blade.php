@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
    <div class="card guest-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="guest-brand mx-auto mb-3">
                    <i class="bi bi-kanban-fill"></i>
                </div>
                <h1 class="h4 fw-bold mb-1">DevTrack Pro</h1>
                <p class="text-muted small mb-0">Connectez-vous pour accéder à votre espace</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/login">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                            class="form-control" placeholder="vous@exemple.com" autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                        <input id="password" type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                </button>
            </form>
        </div>
    </div>
@endsection
