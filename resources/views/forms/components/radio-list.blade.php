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
    <div x-data="{
        selectedLabel: null,
        selectPreviousLabel(currentLabel) {
            const labelElement = currentLabel.closest('label');
            const previousLabel = labelElement.previousElementSibling;
            const allLabels = labelElement.parentNode.querySelectorAll('label');
            allLabels.forEach(label => {
                label.classList.remove('border-b-primary-300');
            });
    
            if (!previousLabel) {
                return;
            }
            previousLabel.classList.add('border-b-primary-300');
        }
    }" class="shadow-sm- overflow-hidden border-collapse bg-white peer">
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

            <label for="{{ $id . '-' . $value }}"
                class="p-4 overflow-hidden flex justify-between items-center w-full border-x border-gray-300 first:rounded-t-md last:rounded-b-md first:border-t last:border-b border-b has-checked:border-primary-300 has-checked:bg-primary-50">
                <div class="flex items-center">
                    <input type="radio" @change="selectPreviousLabel($event.target)"
                        {{ $inputAttributes->class([
                            'fi-radio-input flex-shrink-0',
                            'fi-valid' => !$errors->has($statePath),
                            'fi-invalid' => $errors->has($statePath),
                        ]) }} />

                    <div class="ms-3">
                        <p class="fi-fo-radio-label-text font-medium">
                            {{ $label }}
                        </p>

                        @if ($hasDescription($value))
                            <p class="fi-fo-radio-label-description text-gray-500 dark:text-gray-200">
                                {{ $getDescription($value) }}
                            </p>
                        @endif
                    </div>
                </div>

                @if ($hasExtra($value))
                    <div class="fi-fo-radio-label-description text-gray-500 dark:text-gray-200">
                        {{ $getExtra($value) }}
                    </div>
                @endif
            </label>
        @endforeach
    </div>

</x-dynamic-component>
