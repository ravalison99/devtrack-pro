<h1>Mes notifications</h1>

@forelse ($notifications as $notification)
    <div style="border:1px solid #ccc; padding:8px; margin-bottom:8px; {{ $notification->read_at ? '' : 'background:#eef' }}">
        @if ($notification->type === 'App\Notifications\TaskStatusChangedNotification')
            Tâche « {{ $notification->data['titre'] }} » : {{ $notification->data['ancien_statut'] }} → {{ $notification->data['nouveau_statut'] }}
        @elseif ($notification->type === 'App\Notifications\WeeklyReportSubmittedNotification')
            {{ $notification->data['stagiaire'] }} a soumis son rapport de la semaine {{ $notification->data['semaine'] }}
        @endif
    </div>
@empty
    <p>Aucune notification.</p>
@endforelse
