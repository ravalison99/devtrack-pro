{{-- Document déposé sur un projet, avec toutes ses versions (la plus récente en premier). --}}
<div class="list-group-item">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <strong>{{ $document->titre }}</strong>
        <span>
            @if ($document->categorie)
                <span class="badge bg-light text-dark border">{{ $document->categorie }}</span>
            @endif
            @if ($document->libelleTypeRapport())
                <span class="badge bg-info text-dark">{{ $document->libelleTypeRapport() }}</span>
            @endif
        </span>
    </div>

    <ul class="list-unstyled mb-0 small">
        @foreach ($document->versions as $version)
            <li class="d-flex justify-content-between align-items-center py-1">
                <span>
                    Version {{ $version->numero_version }}
                    @if ($loop->first)
                        <span class="badge bg-success">Version actuelle</span>
                    @else
                        <span class="badge bg-secondary">Ancienne version</span>
                    @endif
                    <span class="text-muted">
                        — {{ $version->created_at->format('d/m/Y H:i') }}
                        @if ($version->utilisateur)
                            par {{ $version->utilisateur->name }}
                        @endif
                    </span>
                </span>
                <span class="d-flex gap-1">
                    <a href="{{ route('documents.download', [$document->id, $version->id]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i>Télécharger
                    </a>
                    {{-- Seul le chef de groupe qui a déposé ce fichier peut le retirer. --}}
                    @can('delete', $version)
                        <form method="POST" action="{{ route('documents.versions.destroy', [$document->id, $version->id]) }}"
                              onsubmit="return confirm('Retirer définitivement cette version du fichier ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Retirer
                            </button>
                        </form>
                    @endcan
                </span>
            </li>
        @endforeach
    </ul>
</div>
