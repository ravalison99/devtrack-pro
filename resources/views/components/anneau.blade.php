{{-- Anneau de progression : pourcentage 0-100. L'animation est déclenchée par devtrack.js. --}}
@props(['pourcentage' => 0, 'taille' => '6.5rem', 'libelle' => null])

@php($valeur = max(0, min(100, (int) $pourcentage)))

<div {{ $attributes->merge(['class' => 'anneau']) }} style="--taille: {{ $taille }}; --p: {{ $valeur }}" data-valeur="{{ $valeur }}"
     role="img" aria-label="{{ $libelle ?? 'Progression' }} : {{ $valeur }} %">
    <span>{{ $valeur }}<small>%</small></span>
</div>
