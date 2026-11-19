@props(['date' => null, 'statut' => null])

@php
    $classe = '';
    $texte = null;
    $icone = 'bi-calendar-event';

    if ($date) {
        $jours = (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);

        if ($statut === 'termine') {
            $texte = $date->format('d/m/Y');
            $icone = 'bi-calendar-check';
        } elseif ($jours < 0) {
            $texte = 'En retard de '.abs($jours).' j';
            $classe = 'retard';
            $icone = 'bi-exclamation-triangle-fill';
        } elseif ($jours === 0) {
            $texte = "Aujourd'hui";
            $classe = 'proche';
            $icone = 'bi-alarm-fill';
        } elseif ($jours === 1) {
            $texte = 'Demain';
            $classe = 'proche';
        } elseif ($jours <= 3) {
            $texte = 'Dans '.$jours.' j';
            $classe = 'proche';
        } else {
            $texte = $date->format('d/m/Y');
        }
    }
@endphp

@if ($texte)
    <span {{ $attributes->merge(['class' => 'echeance '.$classe]) }} title="Échéance : {{ $date->format('d/m/Y') }}">
        <i class="bi {{ $icone }} me-1"></i>{{ $texte }}
    </span>
@else
    <span {{ $attributes->merge(['class' => 'echeance']) }}>—</span>
@endif
