@props(['priorite'])

@php
    $libelles = ['basse' => 'Basse', 'moyenne' => 'Moyenne', 'haute' => 'Haute'];
@endphp

<span {{ $attributes->merge(['class' => 'pastille pastille-prio-'.$priorite]) }}>
    <i class="bi bi-flag-fill"></i>{{ $libelles[$priorite] ?? $priorite }}
</span>
