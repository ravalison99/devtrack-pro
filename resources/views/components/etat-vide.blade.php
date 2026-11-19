@props(['icone' => 'bi-inbox', 'titre', 'texte' => null])

<div {{ $attributes->merge(['class' => 'etat-vide']) }}>
    <div class="etat-vide-icone"><i class="bi {{ $icone }}"></i></div>
    <div class="etat-vide-titre">{{ $titre }}</div>
    @if ($texte)
        <div class="small">{{ $texte }}</div>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
