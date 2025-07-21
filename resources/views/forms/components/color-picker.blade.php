<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }}
    }"
        {{ $attributes->merge($getExtraAttributes())->class(['palette-color-picker flex items-center flex-wrap gap-3']) }}>
        @foreach ($getColors() as $key => $color)
            <label x-data
                x-tooltip="{
                    content: '{{ $color['label'] }}',
                    theme: $store.theme,
                }"
                @class([
                    'palette-color-picker-item rounded-full cursor-pointer ring-2
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ring-gray-950/10 dark:ring-white/10
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    focus-within:outline focus-within:outline-white focus-within:outline-2',
                    match ($getSize()) {
                        'xs' => 'size-4',
                        'sm' => 'size-6',
                        'lg' => 'size-10',
                        'xl' => 'size-12',
                        default => 'size-8',
                    },
                ])
                x-bind:class="{
                    'palette-color-picker-item-active !ring-primary-500 ring-offset-2 ring-offset-white dark:ring-offset-black': state === '{{ $key }}'
                }">
                <div {{ \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())->class([
                    'rounded-full h-full w-full flex justify-center items-center',
                    $color['value'] => $color['type'] === 'class',
                ]) }}
                    @if ($color['type'] !== 'class') style="background-color: {{ $color['type'] === 'rgb' ? 'rgba(' . $color['value'] . ', 1)' : $color['value'] }};" @endif>
                    <input type="radio" x-model="state" value="{{ $key }}" class="opacity-0 pointer-events-none"
                        hidden />
                    <span class="sr-only">{{ $color['label'] }}</span>
                    <svg x-show="state === '{{ $key }}'"
                        class="advanced-components-color-button-icon h-5 w-5 text-gray-100"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
                        data-slot="icon" style="">
                        <path fill-rule="evenodd"
                            d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
            </label>
        @endforeach
    </div>
</x-dynamic-component>
