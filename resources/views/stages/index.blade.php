@extends('layouts.app')

@section('title', 'Stages')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title h3 mb-0"><i class="bi bi-mortarboard me-2"></i>Stages</h1>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('stages.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Créer un stage
            </a>
        @endif
    </div>

    @if (auth()->user()->isAdmin() || auth()->user()->isMentor())
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-person-check me-1"></i>Stagiaires actifs</div>
            <div class="list-group list-group-flush">
                @forelse ($stagiairesActifs as $stageActif)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $stageActif->stagiaire->name }}</strong>
                            <span class="text-muted small ms-2">{{ $stageActif->stagiaire->email }}</span>
                        </div>
                        <div class="text-muted small">
                            Mentor : {{ $stageActif->mentor->name }} —
                            {{ $stageActif->date_debut->format('d/m/Y') }} → {{ $stageActif->date_fin->format('d/m/Y') }}
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-3">Aucun stagiaire actif pour le moment.</div>
                @endforelse
            </div>
        </div>
    @endif

    @if (auth()->user()->isMentor())
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('stages.index') }}" class="row g-3 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Statut</label>
                        <select name="statut" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            @foreach (['planifie', 'en_cours', 'termine', 'annule'] as $statut)
                                <option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ $statut }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small">Date de début (à partir de)</label>
                        <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small">Date de fin (jusqu'à)</label>
                        <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-6 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-funnel me-1"></i>Filtrer
                        </button>
                        <a href="{{ route('stages.index') }}" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Stagiaire</th>
                        <th>Email</th>
                        <th>Mentor</th>
                        <th>Statut</th>
                        @if (auth()->user()->isAdmin() || auth()->user()->isMentor())
                            <th class="text-end">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stages as $stage)
                        @php
                            $couleursStatutStage = [
                                'planifie' => 'bg-secondary',
                                'en_cours' => 'bg-primary',
                                'termine' => 'bg-success',
                                'annule' => 'bg-dark',
                            ];
                            $libellesStatutStage = [
                                'planifie' => 'Planifié',
                                'en_cours' => 'En cours',
                                'termine' => 'Terminé',
                                'annule' => 'Annulé',
                            ];
                            $transitions = $transitionsParStage[$stage->id] ?? [];
                        @endphp
                        <tr>
                            <td>{{ $stage->stagiaire->name }}</td>
                            <td>{{ $stage->stagiaire->email }}</td>
                            <td>{{ $stage->mentor->name }}</td>
                            <td>
                                <span class="badge {{ $couleursStatutStage[$stage->statut] ?? 'bg-secondary' }}">
                                    {{ $libellesStatutStage[$stage->statut] ?? $stage->statut }}
                                </span>
                            </td>
                            @if (auth()->user()->isAdmin() || auth()->user()->isMentor())
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        @if (auth()->user()->isMentor())
                                            <a href="{{ route('projects.create', $stage) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-folder-plus me-1"></i>Nouveau projet
                                            </a>
                                        @endif

                                        @if (auth()->user()->isAdmin())
                                            @if (count($transitions) > 0)
                                                <form method="POST" action="{{ route('stages.updateStatus', $stage) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="statut" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block w-auto">
                                                        <option value="{{ $stage->statut }}" selected>{{ $libellesStatutStage[$stage->statut] ?? $stage->statut }}</option>
                                                        @foreach ($transitions as $transition)
                                                            <option value="{{ $transition }}">{{ $libellesStatutStage[$transition] ?? $transition }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            @endif

                                            @if (in_array($stage->statut, ['termine', 'annule'], true))
                                                <form method="POST" action="{{ route('stages.destroy', $stage) }}" class="d-inline" onsubmit="return confirm('Supprimer définitivement ce stage ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Aucun stage pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($stages->hasPages())
            <div class="card-footer bg-white">
                {{ $stages->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
