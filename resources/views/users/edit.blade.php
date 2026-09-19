@extends('layouts.app')

@section('title', 'Modifier un utilisateur')

@section('content')
    <a href="{{ route('users.index') }}" class="text-decoration-none small text-muted d-inline-block mb-2">
        <i class="bi bi-arrow-left me-1"></i>Retour aux utilisateurs
    </a>

    <h1 class="page-title h3 mb-4"><i class="bi bi-person-gear me-2"></i>Modifier {{ $user->name }}</h1>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select name="role" class="form-select">
                                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                                <option value="mentor" @selected(old('role', $user->role) === 'mentor')>Mentor</option>
                                <option value="stagiaire" @selected(old('role', $user->role) === 'stagiaire')>Stagiaire</option>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" value="1" id="estActif" class="form-check-input" @checked(old('is_active', $user->is_active))>
                            <label for="estActif" class="form-check-label">Compte actif</label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
