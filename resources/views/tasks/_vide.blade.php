@if ($filtreActif)
    <x-etat-vide icone="bi-funnel" titre="Aucune tâche ne correspond"
        texte="Essayez un autre filtre ou réinitialisez la recherche.">
        <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-primary">Voir toutes les tâches</a>
    </x-etat-vide>
@else
    <x-etat-vide icone="bi-clipboard-check" titre="Aucune tâche pour le moment"
        :texte="auth()->user()->isStagiaire() ? 'Les tâches qui vous sont confiées apparaîtront ici.' : 'Créez une première tâche pour démarrer.'">
        @if ($peutCreer)
            <a href="{{ route('tasks.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Créer une tâche</a>
        @endif
    </x-etat-vide>
@endif
