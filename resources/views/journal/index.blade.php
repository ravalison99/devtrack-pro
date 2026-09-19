@extends('layouts.app')

@section('title', 'Journal')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-journal-text me-2"></i>Mon journal quotidien</h1>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-pencil-square me-1"></i>Nouvelle entrée</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('journal.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contenu</label>
                            <textarea name="contenu" rows="6" class="form-control">{{ old('contenu') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history me-1"></i>Historique</div>
                <div class="list-group list-group-flush">
                    @forelse ($entries as $entry)
                        <div class="list-group-item">
                            <strong>{{ $entry->date->format('d/m/Y') }}</strong>
                            <p class="mb-0 text-muted">{{ $entry->contenu }}</p>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucune entrée pour le moment.</div>
                    @endforelse
                </div>
                @if ($entries->hasPages())
                    <div class="card-footer bg-white">
                        {{ $entries->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
