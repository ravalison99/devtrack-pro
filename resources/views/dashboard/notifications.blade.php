@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="page-title h3 mb-0"><i class="bi bi-bell me-2"></i>Mes notifications</h1>

        <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group" aria-label="Filtre">
                <a href="{{ route('notifications.index') }}" class="btn {{ $seulementNonLues ? 'btn-outline-primary' : 'btn-primary' }}">Toutes</a>
                <a href="{{ route('notifications.index', ['filtre' => 'non_lues']) }}" class="btn {{ $seulementNonLues ? 'btn-primary' : 'btn-outline-primary' }}">
                    Non lues
                    @if ($nombreNonLues > 0)
                        <span class="badge bg-danger rounded-pill ms-1">{{ $nombreNonLues }}</span>
                    @endif
                </a>
            </div>

            @if ($nombreNonLues > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-check2-all me-1"></i>Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php
                    $message = $notification->data['message'] ?? $notification->data['titre'] ?? 'Notification';
                    $icone = $notification->data['icone'] ?? 'bi-bell';
                @endphp
                <div class="list-group-item d-flex gap-3 justify-content-between align-items-center {{ $notification->read_at ? '' : 'notif-non-lue' }}">
                    <a href="{{ route('notifications.open', $notification->id) }}" class="d-flex gap-3 text-decoration-none text-body flex-grow-1">
                        <i class="bi {{ $icone }} fs-5 mt-1 {{ $notification->read_at ? 'text-muted' : 'text-primary' }}"></i>
                        <div>
                            <div class="{{ $notification->read_at ? '' : 'fw-semibold' }}">{{ $message }}</div>
                            <div class="text-muted small">
                                {{ $notification->created_at->diffForHumans() }}
                                @unless ($notification->read_at)
                                    <span class="badge bg-primary ms-1">Nouveau</span>
                                @endunless
                            </div>
                        </div>
                    </a>

                    <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" onsubmit="return confirm('Supprimer cette notification ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            @empty
                <x-etat-vide icone="bi-bell-slash" :titre="$seulementNonLues ? 'Aucune notification non lue' : 'Aucune notification'"
                    texte="Vous serez prévenu(e) ici des tâches, commentaires et documents qui vous concernent." />
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer bg-white">
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
