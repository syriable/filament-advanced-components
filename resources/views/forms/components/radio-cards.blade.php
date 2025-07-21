@php
    use Filament\Support\Enums\GridDirection;
    use Illuminate\View\ComponentAttributeBag;

    $fieldWrapperView = $getFieldWrapperView();
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $gridDirection = $getGridDirection() ?? GridDirection::Column;
    $id = $getId();
    $isDisabled = $isDisabled();
    $isInline = $isInline();
    $statePath = $getStatePath();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        {{ $getExtraAttributeBag()->when(!$isInline, fn(ComponentAttributeBag $attributes) => $attributes->grid($getColumns(), $gridDirection))->class(['fi-fo-radio', 'fi-inline' => $isInline]) }}>
        @foreach ($getOptions() as $value => $label)
            @php
                $inputAttributes = $extraInputAttributeBag->merge(
                    [
                        'disabled' => $isDisabled || $isOptionDisabled($value, $label),
                        'id' => $id . '-' . $value,
                        'name' => $id,
                        'value' => $value,
                        'wire:loading.attr' => 'disabled',
                        $wireModelAttribute => $statePath,
                    ],
                    escape: false,
                );
            @endphp

            <label class="fi-fo-radio-label w-full ">
                <x-filament::input.wrapper class="w-full">
                    <div class="flex flex-col px-2 py-3 space-y-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="fi-fo-radio-label-text ms-3">
                                <p>
                                    {{ $label }}
                                </p>

                                @if ($hasDescription($value))
                                    <p class="fi-fo-radio-label-description">
                                        {{ $getDescription($value) }}
                                    </p>
                                @endif
                            </div>
                            <input type="radio"
                                {{ $inputAttributes->class([
                                    'fi-radio-input flex-shrink-0',
                                    'fi-valid' => !$errors->has($statePath),
                                    'fi-invalid' => $errors->has($statePath),
                                ]) }} />
                        </div>
                        @if ($hasExtra($value))
                            <p class="fi-fo-radio-label-text ms-2">
                                {{ $getExtra($value) }}
                            </p>
                        @endif
                    </div>
                </x-filament::input.wrapper>
            </label>
        @endforeach
    </div>
</x-dynamic-component>
