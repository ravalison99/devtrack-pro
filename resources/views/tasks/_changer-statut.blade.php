{{-- Menu de changement de statut : n'affiche que les transitions autorisées pour l'utilisateur. --}}
@php
    $libelles = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'en_revue' => 'En revue', 'termine' => 'Terminé'];
@endphp

@if (count($transitions) > 0)
    <form method="POST" action="{{ route('tasks.updateStatus', $task) }}">
        @csrf
        @method('PATCH')
        <select name="statut" onchange="this.form.submit()" class="form-select form-select-sm" aria-label="Changer le statut de la tâche {{ $task->titre }}">
            <option value="{{ $task->statut }}" selected>{{ $libelles[$task->statut] ?? $task->statut }}</option>
            @foreach ($transitions as $transition)
                <option value="{{ $transition }}">→ {{ $libelles[$transition] ?? $transition }}</option>
            @endforeach
        </select>
    </form>
@else
    <span class="text-muted small">Aucune action possible</span>
@endif
