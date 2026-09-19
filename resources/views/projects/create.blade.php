@extends('layouts.app')

@section('title', 'Créer un projet')

@section('content')
    <h1 class="page-title h3 mb-1"><i class="bi bi-folder2-open me-2"></i>Créer un projet</h1>
    @if ($stage)
        <p class="text-muted mb-4">
            Stage #{{ $stage->id }} — Stagiaire : {{ $stage->stagiaire->name }} — Mentor : {{ $stage->mentor->name }}
        </p>
    @endif

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('projects.store') }}">
                        @csrf

                        @if ($stage)
                            <input type="hidden" name="stage_id" value="{{ $stage->id }}">
                        @else
                            <div class="mb-3">
                                <label class="form-label">Stage</label>
                                <select name="stage_id" class="form-select">
                                    <option value="">— Sélectionner un stage —</option>
                                    @foreach ($stages as $s)
                                        <option value="{{ $s->id }}" @selected(old('stage_id') == $s->id)>
                                            #{{ $s->id }} — {{ $s->stagiaire->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Nom du projet</label>
                            <input type="text" name="nom" value="{{ old('nom') }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Créer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
