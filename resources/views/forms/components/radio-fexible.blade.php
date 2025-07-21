@php
    $fieldWrapperView = $getFieldWrapperView();
    $hasInlineLabel = $hasInlineLabel();
    $id = $getId();
    $isDisabled = $isDisabled();
    $isMultiple = $isMultiple();
    $statePath = $getStatePath();
    $areButtonLabelsHidden = $areButtonLabelsHidden();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
    $extraInputAttributeBag = $getExtraInputAttributeBag()->class(['fi-fo-toggle-buttons-input']);
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field" :has-inline-label="$hasInlineLabel" class="fi-fo-toggle-buttons-wrp">
    <div x-data="{
        open: false,
        custom: null,
        options: @js(array_keys($getOptions())),
        isCustomValue() {
            return this.custom !== null && !this.options.includes(this.custom.toString());
        }
    }"
        {{ $getExtraAttributeBag()->class(['fi-fo-toggle-buttons fi-btn-group overflow-x-auto w-full whitespace-nowrap']) }}>
        @foreach ($getOptions() as $value => $label)
            @php
                $inputId = "{$id}-{$value}";
                $shouldOptionBeDisabled = $isDisabled || $isOptionDisabled($value, $label);
                $color = $getColor($value);
                $icon = $getIcon($value);
            @endphp

            <input @disabled($shouldOptionBeDisabled) id="{{ $inputId }}"
                @if (!$isMultiple) name="{{ $id }}" @endif
                type="{{ $isMultiple ? 'checkbox' : 'radio' }}" value="{{ $value }}" wire:loading.attr="disabled"
                {{ $wireModelAttribute }}="{{ $statePath }}" {{ $extraInputAttributeBag }} />

            <x-filament::button :color="$color" :disabled="$shouldOptionBeDisabled" :for="$inputId" grouped :icon="$icon"
                :label-sr-only="$areButtonLabelsHidden" tag="label">
                {{ $label }}
            </x-filament::button>
        @endforeach
        @if ($isFexible())
            <input @disabled($shouldOptionBeDisabled) id="fexible"
                @if (!$isMultiple) name="{{ $id }}" @endif
                type="{{ $isMultiple ? 'checkbox' : 'radio' }}" x-modelable="custom" wire:loading.attr="disabled"
                {{ $extraInputAttributeBag }} x-ref='fex' />


            <x-filament::button class="relative overflow-hidden"
                @click.prevent="open = !open; $wire.{{ $statePath }} = null; custom = null"
                @click.outside="open = false" @keydown.enter="open=false" x-trap="open" for="fexible"
                icon="heroicon-o-pencil" icon-size='xs' :color="$color" :disabled="$shouldOptionBeDisabled" grouped :label-sr-only="$areButtonLabelsHidden"
                tag="label">
                <span :class="{ 'invisible': open }" class="relative w-full h-full">
                    <template x-if="isCustomValue()">
                        <div>
                            <span x-text="custom"></span>
                            @if ($getLabel())
                                <span class="ms-1">{{ $getLabel() }}</span>
                            @endif
                        </div>
                    </template>

                    <template x-if="!isCustomValue()">
                        <span>other</span>
                    </template>
                </span>

                <input x-cloak x-show="open" x-model='custom' type="{{ $getType() }}"
                    placeholder="enter custom value" @change='$refs.fex.click()'
                    {{ $wireModelAttribute }}="{{ $statePath }}"
                    class="absolute dark:bg-gray-800 inset-0 w-full h-full m-0 px-1 text-center border-none outline-none focus:ring-0 focus:outline-none bg-gray-100" />
            </x-filament::button>
        @endif
    </div>
</x-dynamic-component>
