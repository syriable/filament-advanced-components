{{--
    PhoneInput — an international phone field backed by libphonenumber.

    The visible input edits the NATIONAL number; the country (and therefore the
    calling code) lives in the selector beside it. The client composes a
    self-describing E.164 transport value and entangles it as the field's state,
    but the server re-parses, validates, and normalises that value — the PHP
    side is the source of truth. The inputs carry no `name`: the value travels
    to Livewire through the entangled `state` alone.
--}}
@php
    use Filament\Support\Facades\FilamentAsset;

    $vm = $getViewModel();
    $statePath = $getStatePath();
    $fieldWrapperView = $getFieldWrapperView();
    $hasError = $errors->has($statePath);

    $id = $getId();
    $listboxId = $id . '-countries';
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field" class="fi-fo-phone-input-wrp">
    <div x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('phone-input', 'syriable/filament-advanced-components') }}"
        x-data="phoneInput({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            initialCountry: @js($vm->selectedCountry?->iso),
            initialNational: @js($vm->nationalValue),
            initialExtension: @js($vm->number->extension),
            placeholder: @js($vm->placeholder),
            ...@js($vm->alpineConfig()),
        })" x-on:keydown.escape="close()" x-on:click.outside="close()" wire:ignore
        wire:key="{{ $getLivewireKey() }}.phone"
        {{ $attributes->merge($getExtraAlpineAttributes(), escape: false)->class(['fi-phone-input', 'fi-phone-input-has-error' => $hasError, 'fi-phone-input-disabled' => $vm->isDisabled]) }}>
        <div class="fi-phone-input-control">
            {{-- Country selector --}}
            @if ($vm->hasCountrySelector)
                <button type="button" class="fi-phone-input-country" x-on:click="toggle()"
                    x-bind:aria-expanded="open ? 'true' : 'false'" aria-haspopup="listbox"
                    aria-controls="{{ $listboxId }}"
                    aria-label="{{ trans('filament-advanced-components::phone-input.select_country') }}"
                    @disabled($vm->isDisabled || $vm->isReadOnly)>
                    @if ($vm->showFlags)
                        <span class="fi-phone-input-flag" x-text="selectedCountry?.flag"
                            aria-hidden="true">{{ $vm->selectedCountry?->flag }}</span>
                    @endif

                    @if ($vm->showDialCode)
                        <span class="fi-phone-input-dial-code"
                            x-text="'+' + dialCode">+{{ $vm->selectedCountry?->dialCode }}</span>
                    @endif

                    <svg class="fi-phone-input-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            @endif

            {{-- National number input --}}
            <input x-ref="input" type="tel" inputmode="tel" autocomplete="tel-national" autocorrect="off"
                spellcheck="false" class="fi-phone-input-field" id="{{ $id }}" value="{{ $vm->displayValue }}"
                x-bind:value="national" x-bind:placeholder="placeholder"
                placeholder="{{ $vm->resolvedPlaceholder() }}"
                @if ($hasError) aria-invalid="true" @endif @disabled($vm->isDisabled)
                @readonly($vm->isReadOnly) @if ($vm->isRequired)
            required
            @endif
            @if ($vm->isAutofocused)
                autofocus
            @endif
            x-on:input="onInput($event)"
            x-on:paste="onPaste($event)"
            />

            {{-- Extension --}}
            @if ($vm->hasExtension)
                <span class="fi-phone-input-ext-label"
                    aria-hidden="true">{{ trans('filament-advanced-components::phone-input.extension_abbr') }}</span>
                <input type="text" inputmode="numeric" class="fi-phone-input-ext"
                    value="{{ $vm->number->extension }}" x-bind:value="extension"
                    maxlength="{{ $vm->maxExtensionLength }}"
                    aria-label="{{ trans('filament-advanced-components::phone-input.extension') }}"
                    @disabled($vm->isDisabled) @readonly($vm->isReadOnly) x-on:input="onExtensionInput($event)" />
            @endif

            {{-- Affordances --}}
            @if ($vm->clearable)
                <button type="button" class="fi-phone-input-action" x-show="national.length > 0" x-on:click="clear()"
                    aria-label="{{ trans('filament-advanced-components::phone-input.clear') }}"
                    @disabled($vm->isDisabled || $vm->isReadOnly)>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif

            @if ($vm->copyable)
                <button type="button" class="fi-phone-input-action" x-on:click="copy()"
                    aria-label="{{ trans('filament-advanced-components::phone-input.copy') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                    </svg>

                </button>
            @endif
        </div>

        {{-- Dropdown --}}
        @if ($vm->hasCountrySelector)
            <div class="fi-phone-input-dropdown" x-show="open" x-cloak x-transition.opacity role="dialog">
                @if ($vm->searchEnabled)
                    <div class="fi-phone-input-search-wrp">
                        <input x-ref="search" type="search" class="fi-phone-input-search"
                            x-model.debounce.{{ $vm->searchDebounce }}ms="search"
                            x-on:keydown="onSearchKeydown($event)"
                            placeholder="{{ trans('filament-advanced-components::phone-input.search_placeholder') }}"
                            aria-label="{{ trans('filament-advanced-components::phone-input.search_placeholder') }}" />
                    </div>
                @endif

                <ul x-ref="list" id="{{ $listboxId }}" class="fi-phone-input-list" role="listbox"
                    aria-label="{{ trans('filament-advanced-components::phone-input.select_country') }}">
                    {{--
                        The loop variable is deliberately NOT named `country`:
                        that's also the name of this component's own reactive
                        `country` property (the selected ISO). Alpine binds
                        `this` for a method called from an `x-on` expression to
                        the merged scope at that call site — inside this loop,
                        that merged scope would have TWO `country` keys (the
                        iteration item here, the component's string outside).
                        `selectCountry()` assigns `this.country = iso`; with a
                        same-named loop variable that write lands on the
                        shadowed loop scope instead of the component, silently
                        no-opping the update *and* permanently corrupting that
                        list item's bound data (it forever renders from the
                        stale, now-string "country" instead of the original
                        option object). `option` sidesteps the collision
                        entirely, so plain `country` below unambiguously means
                        "the selected ISO".
                    --}}
                    <template x-for="(option, i) in filteredCountries" :key="option.iso">
                        <li class="fi-phone-input-option" role="option"
                            x-bind:data-active="i === activeIndex ? 'true' : 'false'"
                            x-bind:aria-selected="option.iso === country ? 'true' : 'false'"
                            x-bind:class="{
                                'fi-phone-input-option-active': i === activeIndex,
                                'fi-phone-input-option-selected': option.iso === country,
                            }"
                            x-on:click="selectCountry(option.iso)" x-on:mouseenter="activeIndex = i">
                            @if ($vm->showFlags)
                                <span class="fi-phone-input-flag" x-text="option.flag" aria-hidden="true"></span>
                            @endif
                            <span class="fi-phone-input-option-name" x-text="option.name"></span>
                            <span class="fi-phone-input-option-dial" x-text="'+' + option.dialCode"></span>
                        </li>
                    </template>

                    <li class="fi-phone-input-empty" x-show="filteredCountries.length === 0">
                        {{ trans('filament-advanced-components::phone-input.no_results') }}
                    </li>
                </ul>
            </div>
        @endif

        {{-- Example hint --}}
        @if ($vm->showExample)
            <p class="fi-phone-input-example" x-show="example" aria-hidden="true">
                {{ trans('filament-advanced-components::phone-input.example') }}
                <span x-text="example">{{ $vm->selectedExample() }}</span>
            </p>
        @endif

        {{-- Line-type badge (server-resolved) --}}
        @if ($vm->showType && $vm->number->isParsed())
            <p class="fi-phone-input-type">{{ $vm->number->type->label() }}</p>
        @endif
    </div>
</x-dynamic-component>
