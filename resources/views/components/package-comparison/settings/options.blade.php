{{--
    Inline options editor for select/radio rows, shown in the row's settings
    popover. Options are plain strings on `row.config.options`; empty entries
    are pruned server-side by `SelectRow::normalizeConfig()`.
    Scope: `row`, `$rowType`.
--}}
<template x-if="row.type === @js($rowType->getName())">
    <div class="fi-pc-settings-body">
        <p class="fi-pc-settings-heading">
            {{ __('filament-advanced-components::package-comparison.settings.options') }}
        </p>

        <template x-for="(option, optionIndex) in row.config.options" x-bind:key="optionIndex">
            <div class="fi-pc-settings-option">
                <input
                    type="text"
                    class="fi-pc-input"
                    x-model="row.config.options[optionIndex]"
                    placeholder="{{ __('filament-advanced-components::package-comparison.settings.option_placeholder') }}"
                />

                <button
                    type="button"
                    class="fi-pc-icon-btn fi-pc-danger"
                    x-on:click="row.config.options.splice(optionIndex, 1)"
                    aria-label="{{ __('filament-advanced-components::package-comparison.actions.remove_option') }}"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="fi-pc-icon" />
                </button>
            </div>
        </template>

        <button
            type="button"
            class="fi-pc-btn"
            x-on:click="row.config.options.push('')"
        >
            <x-filament::icon icon="heroicon-m-plus" class="fi-pc-icon" />
            {{ __('filament-advanced-components::package-comparison.actions.add_option') }}
        </button>
    </div>
</template>
