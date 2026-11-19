{{-- items : liste de [libellé, url|null] ; le dernier élément est la page courante --}}
@props(['items' => []])

<nav class="fil-ariane" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span class="visually-hidden">Accueil</span></a></li>
        @foreach ($items as $item)
            @if ($loop->last || empty($item[1]))
                <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($item[0], 48) }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $item[1] }}">{{ $item[0] }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>
