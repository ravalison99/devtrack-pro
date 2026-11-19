@props(['label', 'valeur', 'icone' => 'bi-bar-chart', 'ton' => '#4f46e5', 'lien' => null])

@php($balise = $lien ? 'a' : 'div')

<{{ $balise }} @if ($lien) href="{{ $lien }}" @endif {{ $attributes->merge(['class' => 'card stat'.($lien ? ' card-lift sans-soulignement text-reset' : '')]) }}>
    <span class="stat-icone" style="--tone: {{ $ton }}"><i class="bi {{ $icone }}"></i></span>
    <span>
        <span class="stat-valeur d-block" data-compte="{{ $valeur }}">{{ $valeur }}</span>
        <span class="stat-label">{{ $label }}</span>
    </span>
</{{ $balise }}>
