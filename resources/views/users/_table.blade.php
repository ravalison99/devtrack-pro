@php
    $colonnesTriables = ['name' => 'Nom', 'email' => 'Email', 'role' => 'Rôle', 'is_active' => 'Statut', 'created_at' => 'Créé le'];
    $directionSuivante = $direction === 'asc' ? 'desc' : 'asc';
    $couleursRole = ['admin' => 'bg-danger', 'mentor' => 'bg-info text-dark', 'stagiaire' => 'bg-primary'];
@endphp

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                @foreach ($colonnesTriables as $cle => $libelle)
                    <th>
                        <a href="{{ route('users.index', array_filter(['sort' => $cle, 'direction' => $champ === $cle ? $directionSuivante : 'asc', 'search' => request('search')])) }}" class="text-decoration-none text-body">
                            {{ $libelle }}
                            @if ($champ === $cle)
                                <i class="bi {{ $direction === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill' }}"></i>
                            @endif
                        </a>
                    </th>
                @endforeach
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($utilisateurs as $utilisateur)
                <tr>
                    <td>{{ $utilisateur->name }}</td>
                    <td>{{ $utilisateur->email }}</td>
                    <td><span class="badge {{ $couleursRole[$utilisateur->role] ?? 'bg-secondary' }}">{{ $utilisateur->role }}</span></td>
                    <td>
                        @if ($utilisateur->is_active)
                            <span class="badge bg-success">Actif</span>
                        @else
                            <span class="badge bg-secondary">Inactif</span>
                        @endif
                    </td>
                    <td>{{ $utilisateur->created_at->format('d/m/Y') }}</td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('users.edit', $utilisateur) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if ($utilisateur->id !== auth()->id() && $utilisateur->is_active)
                                <form method="POST" action="{{ route('users.destroy', $utilisateur) }}" onsubmit="return confirm('Désactiver ce compte ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Aucun utilisateur ne correspond à la recherche.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($utilisateurs->hasPages())
    <div class="card-footer bg-white">
        {{ $utilisateurs->links('pagination::bootstrap-5') }}
    </div>
@endif
