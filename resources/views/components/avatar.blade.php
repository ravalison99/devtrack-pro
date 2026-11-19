@props(['user' => null, 'taille' => null])

@php
    $nom = $user?->name;
    $mots = $nom ? preg_split('/\s+/', trim($nom)) : [];
    $initiales = $nom
        ? mb_strtoupper(mb_substr($mots[0], 0, 1).(count($mots) > 1 ? mb_substr(end($mots), 0, 1) : mb_substr($mots[0], 1, 1)))
        : '?';
    $teinte = $nom ? crc32($nom) % 360 : 0;
    $classeTaille = $taille ? 'avatar-'.$taille : '';
@endphp

<span {{ $attributes->merge(['class' => 'avatar '.$classeTaille.($nom ? '' : ' avatar-inconnu')]) }}
      style="--h: {{ $teinte }}" title="{{ $nom ?? 'Compte supprimé' }}" aria-label="{{ $nom ?? 'Compte supprimé' }}">{{ $initiales }}</span>
