{{--
    OtpInput — a row of single-character cells backing one scalar string.

    The server renders every cell (so the code shows instantly, even before
    Alpine boots and when JS is slow), and the entangled Alpine component then
    owns all interaction. The cells carry no `name`: the value travels to
    Livewire through the entangled `state`, so the individual inputs are pure
    presentation and never post anything themselves.
--}}
@php
    use Filament\Support\Facades\FilamentAsset;

    $vm = $getViewModel();
    $statePath = $getStatePath();
    $fieldWrapperView = $getFieldWrapperView();

    $state = (string) ($getState() ?? '');
    $hasError = $errors->has($statePath);

    $groupLabel = $getLabel();
    $cellLabelKey = 'filament-advanced-components::otp-input.cell_label';

    $displayCharacter = function (int $index) use ($state, $vm): string {
        $character = mb_substr($state, $index, 1);

        if ($character === '') {
            return '';
        }

        return $vm->isPrivate ? $vm->maskCharacter : $character;
    };
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    class="fi-fo-otp-input-wrp"
>
    <div
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('otp-input', 'syriable/filament-advanced-components') }}"
        x-data="otpInput({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            length: @js($vm->length),
            characterClass: @js($vm->mode->characterClass()),
            isPrivate: @js($vm->isPrivate),
            maskCharacter: @js($vm->maskCharacter),
            isDisabled: @js($vm->isDisabled),
            isReadOnly: @js($vm->isReadOnly),
            hasAutocomplete: @js($vm->hasAutocomplete),
            shouldAutoSubmit: @js($vm->shouldAutoSubmit),
            autoSubmitAction: @js($vm->autoSubmitAction),
        })"
        wire:ignore
        wire:key="{{ $getLivewireKey() }}.{{ substr(md5(serialize([$vm->length, $vm->isDisabled, $vm->isReadOnly, $vm->isPrivate])), 0, 16) }}"
        {{
            $attributes
                ->merge($getExtraAlpineAttributes(), escape: false)
                ->class([
                    'fi-otp-input',
                    'fi-otp-input-size-' . $vm->size->value,
                    'fi-otp-input-shape-' . $vm->shape->value,
                    'fi-otp-input-private' => $vm->isPrivate,
                    'fi-otp-input-disabled' => $vm->isDisabled,
                    'fi-otp-input-has-error' => $hasError,
                ])
                ->style([$vm->rootStyles()])
        }}
        role="group"
        @if (filled($groupLabel)) aria-label="{{ $groupLabel }}" @endif
    >
        @foreach ($vm->groupedIndexes() as $groupIndex => $group)
            @if ($groupIndex > 0 && filled($vm->separator))
                <span class="fi-otp-input-separator" aria-hidden="true">{{ $vm->separator }}</span>
            @endif

            <div class="fi-otp-input-group">
                @foreach ($group as $index)
                    <input
                        type="text"
                        data-otp-cell
                        data-otp-index="{{ $index }}"
                        inputmode="{{ $vm->mode->inputMode() }}"
                        autocomplete="{{ ($index === 0 && $vm->hasAutocomplete) ? 'one-time-code' : 'off' }}"
                        autocapitalize="{{ $vm->mode->autoCapitalize() }}"
                        autocorrect="off"
                        spellcheck="false"
                        maxlength="1"
                        class="fi-otp-input-cell"
                        value="{{ $displayCharacter($index) }}"
                        @if (filled($vm->placeholderFor($index))) placeholder="{{ $vm->placeholderFor($index) }}" @endif
                        aria-label="{{ trans($cellLabelKey, ['position' => $index + 1, 'total' => $vm->length]) }}"
                        @if ($hasError) aria-invalid="true" @endif
                        @disabled($vm->isDisabled)
                        @readonly($vm->isReadOnly)
                        @if ($vm->isAutofocused && $index === 0) autofocus @endif
                        x-on:input="onInput($event, {{ $index }})"
                        x-on:keydown="onKeydown($event, {{ $index }})"
                        x-on:paste="onPaste($event, {{ $index }})"
                        x-on:focus="onFocus($event)"
                    />
                @endforeach
            </div>
        @endforeach
    </div>
</x-dynamic-component>
