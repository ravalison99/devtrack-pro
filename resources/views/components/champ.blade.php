@props(['label', 'name', 'type' => 'text', 'value' => null])

<div class="mb-3">
    <label for="champ-{{ $name }}" class="form-label">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="champ-{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'form-control', 'rows' => 6]) }}>{{ $value }}</textarea>
    @else
        <input id="champ-{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" {{ $attributes->merge(['class' => 'form-control']) }}>
    @endif
</div>
