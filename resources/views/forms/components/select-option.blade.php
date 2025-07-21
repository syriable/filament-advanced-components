<div class="palette-select-option">
    @if ($color['type'] === 'class')
        <span class="{{ $color['value'] }}"></span>
    @else
        <span
            style="background-color: {{ $color['type'] === 'rgb' ? 'rgba(' . $color['value'] . ', 1)' : $color['value'] }};"></span>
    @endif
    <span style="line-height: 1; display: block; height: 1rem;">{{ $color['label'] }}</span>
</div>
