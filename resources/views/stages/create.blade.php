@extends('layouts.app')

@section('title', 'Créer un stage')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-mortarboard me-2"></i>Créer un stage</h1>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('stages.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Stagiaire</label>
                                <select name="stagiaire_id" class="form-select">
                                    @foreach ($stagiaires as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mentor</label>
                                <select name="mentor_id" class="form-select">
                                    @foreach ($mentors as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date de début</label>
                                <input type="date" name="date_debut" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date de fin</label>
                                <input type="date" name="date_fin" class="form-control">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Mode de travail</label>
                                <select name="mode_travail" class="form-select">
                                    <option value="presentiel">Présentiel</option>
                                    <option value="hybride">Hybride</option>
                                    <option value="teletravail">Télétravail</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-4">
                            <i class="bi bi-check-lg me-1"></i>Créer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
