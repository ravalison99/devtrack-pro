@extends('layouts.app')

@section('title', 'Rapports hebdomadaires')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-file-earmark-bar-graph me-2"></i>Mes rapports hebdomadaires</h1>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-send me-1"></i>Soumettre un rapport</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('reports.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Semaine</label>
                            <input type="number" name="semaine" min="1" max="12" value="{{ old('semaine') }}" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contenu</label>
                            <textarea name="contenu" rows="6" class="form-control">{{ old('contenu') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Soumettre
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history me-1"></i>Historique</div>
                <div class="list-group list-group-flush">
                    @php
                        $couleursStatutRapport = ['soumis' => 'bg-secondary', 'valide' => 'bg-success', 'a_corriger' => 'bg-warning text-dark'];
                    @endphp
                    @forelse ($reports as $report)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Semaine {{ $report->semaine }}</strong>
                                <span class="badge ms-2 {{ $couleursStatutRapport[$report->statut] ?? 'bg-secondary' }}">{{ $report->statut }}</span>
                            </div>
                            @if ($report->fichier_pdf)
                                <a href="{{ route('reports.download', $report->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>PDF
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucun rapport soumis pour le moment.</div>
                    @endforelse
                </div>
                @if ($reports->hasPages())
                    <div class="card-footer bg-white">
                        {{ $reports->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
