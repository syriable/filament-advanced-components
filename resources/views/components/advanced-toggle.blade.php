{{--
    AdvancedToggle — byte-for-byte the native Toggle's markup and Alpine
    wiring, with one addition: when `requiresConfirmation()` is active, the
    control is wrapped in a thin, non-visual layer that intercepts the click
    *before* it reaches the native switch (see `advanced-toggle/control.blade.php`).
    Without confirmation configured, this file renders exactly what
    `filament-forms::components.toggle` does.
--}}
@php
    use Illuminate\View\ComponentAttributeBag;

    $vm = $getViewModel();
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();

    $entangleExpression = '$wire.' . $applyStateBindingModifiers('$entangle(\'' . $statePath . '\')');

    $attributes = (new ComponentAttributeBag)
        ->merge([
            'aria-checked' => 'false',
            'autofocus' => $isAutofocused(),
            'disabled' => $isDisabled(),
            'id' => $getId(),
            'offColor' => $getOffColor() ?? 'gray',
            'offIcon' => $getOffIcon(),
            'onColor' => $getOnColor() ?? 'primary',
            'onIcon' => $getOnIcon(),
            'state' => $entangleExpression,
            'wire:loading.attr' => 'disabled',
            'wire:target' => $statePath,
        ], escape: false)
        ->when(
            filled($vm->stateTooltip),
            fn (ComponentAttributeBag $attributes) => $attributes->merge(['x-tooltip' => $vm->stateTooltip], escape: false),
        )
        ->merge($getExtraAttributes(), escape: false)
        ->merge($getExtraAlpineAttributes(), escape: false)
        ->class(['fi-fo-toggle']);
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :inline-label-vertical-alignment="\Filament\Support\Enums\VerticalAlignment::Center"
>
    @if ($isInline())
        <x-slot name="labelPrefix">
            @include('filament-advanced-components::components.advanced-toggle.control')
        </x-slot>
    @else
        @include('filament-advanced-components::components.advanced-toggle.control')
    @endif

    @if ($vm->hasMeta())
        @include('filament-advanced-components::components.advanced-toggle.meta')
    @endif
</x-dynamic-component>
