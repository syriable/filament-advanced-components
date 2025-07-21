@php
    use Filament\Support\Enums\GridDirection;

    $fieldWrapperView = $getFieldWrapperView();
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $isHtmlAllowed = $isHtmlAllowed();
    $gridDirection = $getGridDirection() ?? GridDirection::Column;
    $isBulkToggleable = $isBulkToggleable();
    $isDisabled = $isDisabled();
    $isSearchable = $isSearchable();
    $statePath = $getStatePath();
    $options = $getOptions();
    $livewireKey = $getLivewireKey();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('checkbox-list', 'filament/forms') }}"
        x-data="checkboxListFormComponent({
            livewireId: @js($this->getId()),
        })" {{ $getExtraAlpineAttributeBag()->class(['fi-fo-checkbox-list']) }}>
        @if (!$isDisabled)
            @if ($isSearchable)
                <x-filament::input.wrapper inline-prefix :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
                    prefix-icon-alias="forms:components.checkbox-list.search-field"
                    class="fi-fo-checkbox-list-search-input-wrp">
                    <input placeholder="{{ $getSearchPrompt() }}" type="search"
                        x-model.debounce.{{ $getSearchDebounce() }}="search"
                        class="fi-input fi-input-has-inline-prefix" />
                </x-filament::input.wrapper>
            @endif

            @if ($isBulkToggleable && count($options))
                <div x-cloak class="fi-fo-checkbox-list-actions" wire:key="{{ $livewireKey }}.actions">
                    <span x-show="! areAllCheckboxesChecked" x-on:click="toggleAllCheckboxes()"
                        wire:key="{{ $livewireKey }}.actions.select-all">
                        {{ $getAction('selectAll') }}
                    </span>

                    <span x-show="areAllCheckboxesChecked" x-on:click="toggleAllCheckboxes()"
                        wire:key="{{ $livewireKey }}.actions.deselect-all">
                        {{ $getAction('deselectAll') }}
                    </span>
                </div>
            @endif
        @endif

        <div {{ $getExtraAttributeBag()->merge(
                [
                    'x-show' => $isSearchable ? 'visibleCheckboxListOptions.length' : null,
                ],
                escape: false,
            )->class(['fi-fo-checkbox-list-options']) }}
            x-data="{
                selectedLabel: null,
                selectPreviousLabel(currentLabel) {
                    const labelElement = currentLabel.closest('div');
            
                    // Remove the class from all elements first
                    const allDivs = labelElement.parentNode.querySelectorAll('div');
                    allDivs.forEach(div => {
                        div.classList.remove('border-b-primary-300');
                    });
            
                    // Check all inputs and add class to previous element of checked ones
                    const inputs = labelElement.parentNode.querySelectorAll('.fi-checkbox-input');
                    inputs.forEach(input => {
                        if (input.checked) {
                            const inputDiv = input.closest('div');
                            const previousDiv = inputDiv.previousElementSibling;
                            if (previousDiv) {
                                previousDiv.classList.add('border-b-primary-300');
                            }
                        }
                    });
                }
            }">
            @forelse ($options as $value => $label)
                <div wire:key="{{ $livewireKey }}.options.{{ $value }}"
                    @if ($isSearchable) x-show="
                            $el
                                .querySelector('.fi-fo-checkbox-list-option-label')
                                ?.innerText.toLowerCase()
                                .includes(search.toLowerCase()) ||
                                $el
                                    .querySelector('.fi-fo-checkbox-list-option-description')
                                    ?.innerText.toLowerCase()
                                    .includes(search.toLowerCase())
                        " @endif
                    class="fi-fo-checkbox-list-option-ctn bg-white dark:bg-gray-800 p-4 overflow-hidden flex justify-between items-center w-full border-x border-gray-300 first:rounded-t-md last:rounded-b-md first:border-t last:border-b border-b has-checked:border-primary-300 has-checked:bg-primary-50">
                    <label class="fi-fo-checkbox-list-option w-full">
                        <input type="checkbox" @change="selectPreviousLabel($event.target)"
                            {{ $extraInputAttributeBag->merge(
                                    [
                                        'disabled' => $isDisabled || $isOptionDisabled($value, $label),
                                        'value' => $value,
                                        'wire:loading.attr' => 'disabled',
                                        $wireModelAttribute => $statePath,
                                        'x-on:change' => $isBulkToggleable ? 'checkIfAllCheckboxesAreChecked()' : null,
                                    ],
                                    escape: false,
                                )->class(['fi-checkbox-input', 'fi-valid' => !$errors->has($statePath), 'fi-invalid' => $errors->has($statePath)]) }} />

                        <div @class([
                            'fi-fo-checkbox-list-option-text w-full grid gap-3',
                            'grid-cols-3' => $hasDescriptions() && $hasExtras(),
                            'grid-cols-1' => !$hasDescriptions() && !$hasExtras(),
                            'grid-cols-2' =>
                                ($hasDescriptions() && !$hasExtras()) ||
                                (!$hasDescriptions() && $hasExtras()),
                        ])>
                            <span class="fi-fo-checkbox-list-option-label">
                                @if ($isHtmlAllowed)
                                    {!! $label !!}
                                @else
                                    {{ $label }}
                                @endif
                            </span>
                            @if ($hasDescription($value))
                                <p class="fi-fo-checkbox-list-option-description">
                                    {{ $getDescription($value) }}
                                </p>
                            @endif
                            @if ($hasExtra($value))
                                <div class="fi-fo-radio-label-description text-gray-500 dark:text-gray-200 text-end">
                                    {{ $getExtra($value) }}
                                </div>
                            @endif
                        </div>
                    </label>
                </div>
            @empty
                <div wire:key="{{ $livewireKey }}.empty"></div>
            @endforelse
        </div>

        @if ($isSearchable)
            <div x-cloak x-show="search && ! visibleCheckboxListOptions.length"
                class="fi-fo-checkbox-list-no-search-results-message">
                {{ $getNoSearchResultsMessage() }}
            </div>
        @endif
    </div>
</x-dynamic-component>
