@props(['statut'])

@php
    $libelles = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'en_revue' => 'En revue', 'termine' => 'Terminé'];
    $icones = ['a_faire' => 'bi-circle', 'en_cours' => 'bi-arrow-repeat', 'en_revue' => 'bi-eye', 'termine' => 'bi-check-circle-fill'];
@endphp

<span {{ $attributes->merge(['class' => 'pastille pastille-'.$statut]) }}>
    <i class="bi {{ $icones[$statut] ?? 'bi-circle' }}"></i>{{ $libelles[$statut] ?? $statut }}
</span>
