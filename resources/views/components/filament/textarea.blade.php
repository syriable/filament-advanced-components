@php
    use Filament\Support\Enums\VerticalAlignment;
@endphp

@props([
    'field' => null,
    'hasInlineLabel' => null,
    'hasNestedRecursiveValidationRules' => null,
    'helperText' => null,
    'hint' => null,
    'hintActions' => null,
    'hintColor' => null,
    'hintIcon' => null,
    'hintIconTooltip' => null,
    'id' => null,
    'inlineLabelVerticalAlignment' => VerticalAlignment::Start,
    'isDisabled' => null,
    'label' => null,
    'labelPrefix' => null,
    'labelSrOnly' => null,
    'labelSuffix' => null,
    'required' => null,
    'statePath' => null,
])

@php
    if ($field) {
        $hasInlineLabel ??= $field->hasInlineLabel();
        $hasNestedRecursiveValidationRules ??=
            $field instanceof \Filament\Forms\Components\Contracts\HasNestedRecursiveValidationRules;
        $helperText ??= $field->getHelperText();
        $hint ??= $field->getHint();
        $hintActions ??= $field->getHintActions();
        $hintColor ??= $field->getHintColor();
        $hintIcon ??= $field->getHintIcon();
        $hintIconTooltip ??= $field->getHintIconTooltip();
        $id ??= $field->getId();
        $isDisabled ??= $field->isDisabled();
        $label ??= $field->getLabel();
        $labelSrOnly ??= $field->isLabelHidden();
        $required ??= $field->isMarkedAsRequired();
        $statePath ??= $field->getStatePath();
    }

    $hintActions = array_filter(
        $hintActions ?? [],
        fn(\Filament\Forms\Components\Actions\Action $hintAction): bool => $hintAction->isVisible(),
    );

    $hasError =
        filled($statePath) &&
        ($errors->has($statePath) || ($hasNestedRecursiveValidationRules && $errors->has("{$statePath}.*")));
@endphp

<div data-field-wrapper
    {{ $attributes->merge($field?->getExtraFieldWrapperAttributes() ?? [])->class(['fi-fo-field-wrp']) }}>
    @if ($label && $labelSrOnly)
        <label for="{{ $id }}" class="sr-only">
            {{ $label }}
        </label>
    @endif

    <div x-data="{
        length: 0,
        maxlength: @js($field->getMaxLength() ?? 0),
        get counter() {
            return this.maxlength - this.length
        },
        get color() {
            return this.counter < 5 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-400 dark:text-gray-200'
        }
    }" @class([
        'grid gap-y-2',
        'sm:grid-cols-3 sm:gap-x-4' => $hasInlineLabel,
        match ($inlineLabelVerticalAlignment) {
            VerticalAlignment::Start => 'sm:items-start',
            VerticalAlignment::Center => 'sm:items-center',
            VerticalAlignment::End => 'sm:items-end',
        } => $hasInlineLabel,
    ])>
        @if (($label && !$labelSrOnly) || $labelPrefix || $labelSuffix || filled($hint) || $hintIcon || count($hintActions))
            <div @class([
                'flex items-center gap-x-3',
                'justify-between' => !$labelSrOnly || $labelPrefix || $labelSuffix,
                'justify-end' => $labelSrOnly && !($labelPrefix || $labelSuffix),
                $label instanceof \Illuminate\View\ComponentSlot
                    ? $label->attributes->get('class')
                    : null,
            ])>
                @if ($label && !$labelSrOnly)
                    <x-filament-forms::field-wrapper.label :for="$id" :disabled="$isDisabled" :prefix="$labelPrefix"
                        :required="$required" :suffix="$labelSuffix">
                        {{ $label }}
                    </x-filament-forms::field-wrapper.label>
                @elseif ($labelPrefix)
                    {{ $labelPrefix }}
                @elseif ($labelSuffix)
                    {{ $labelSuffix }}
                @endif

                @if (filled($hint) || $hintIcon || count($hintActions))
                    <x-filament-forms::field-wrapper.hint :actions="$hintActions" :color="$hintColor" :icon="$hintIcon"
                        :tooltip="$hintIconTooltip">
                        {{ $hint }}
                    </x-filament-forms::field-wrapper.hint>
                @endif
            </div>
        @endif

        @if (!\Filament\Support\is_slot_empty($slot) || $hasError || filled($helperText))
            <div @class([
                'grid auto-cols-fr gap-y-2',
                'sm:col-span-2' => $hasInlineLabel,
            ])>
                <div class="relative">
                    {{ $slot }}
                    <span x-cloak x-show='maxlength > 0'
                        class="absolute bottom-1 end-5 text-sm dark:text-gray-200 transition-colors"
                        :class="`${color}`" x-text="length+'/'+maxlength"></span>
                </div>
                @if ($hasError)
                    <x-filament-forms::field-wrapper.error-message>
                        {{ $errors->has($statePath) ? $errors->first($statePath) : ($hasNestedRecursiveValidationRules ? $errors->first("{$statePath}.*") : null) }}
                    </x-filament-forms::field-wrapper.error-message>
                @endif

                @if (filled($helperText))
                    <x-filament-forms::field-wrapper.helper-text>
                        {{ $helperText }}
                    </x-filament-forms::field-wrapper.helper-text>
                @endif
            </div>
        @endif
    </div>
</div>
