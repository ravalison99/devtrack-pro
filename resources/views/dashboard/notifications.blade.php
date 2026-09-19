@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <h1 class="page-title h3 mb-4"><i class="bi bi-bell me-2"></i>Mes notifications</h1>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <div class="list-group-item d-flex gap-3 justify-content-between {{ $notification->read_at ? '' : 'bg-light' }}">
                    <div class="d-flex gap-3">
                        <i class="bi {{ $notification->read_at ? 'bi-envelope-open text-muted' : 'bi-envelope-fill text-primary' }} fs-5 mt-1"></i>
                        <div>
                            @if ($notification->type === 'App\Notifications\TaskStatusChangedNotification')
                                Tâche « {{ $notification->data['titre'] }} » :
                                <span class="badge bg-secondary">{{ $notification->data['ancien_statut'] }}</span>
                                <i class="bi bi-arrow-right mx-1"></i>
                                <span class="badge bg-primary">{{ $notification->data['nouveau_statut'] }}</span>
                            @elseif ($notification->type === 'App\Notifications\WeeklyReportSubmittedNotification')
                                <strong>{{ $notification->data['stagiaire'] }}</strong> a soumis son rapport de la semaine {{ $notification->data['semaine'] }}
                            @elseif ($notification->type === 'App\Notifications\StageAssignedNotification')
                                Affectation de stage : <strong>{{ $notification->data['stagiaire'] }}</strong> ↔ <strong>{{ $notification->data['mentor'] }}</strong>
                            @elseif ($notification->type === 'App\Notifications\StageStatusChangedNotification')
                                Le stage de <strong>{{ $notification->data['stagiaire'] }}</strong> :
                                <span class="badge bg-secondary">{{ $notification->data['ancien_statut'] }}</span>
                                <i class="bi bi-arrow-right mx-1"></i>
                                <span class="badge bg-primary">{{ $notification->data['nouveau_statut'] }}</span>
                            @elseif ($notification->type === 'App\Notifications\TaskAssignedNotification')
                                Nouvelle tâche assignée : <strong>{{ $notification->data['titre'] }}</strong> ({{ $notification->data['projet'] }})
                            @elseif ($notification->type === 'App\Notifications\NewUserNotification')
                                Nouvel utilisateur : <strong>{{ $notification->data['name'] }}</strong> ({{ $notification->data['role'] }})
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" onsubmit="return confirm('Supprimer cette notification ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-5">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    Aucune notification.
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer bg-white">
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
