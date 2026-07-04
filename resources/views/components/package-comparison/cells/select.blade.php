{{--
    Dropdown over the row's own options (`row.config.options`). The options
    are stamped by Alpine after `x-model` initializes, so selection is also
    bound through `x-bind:selected` — a well-known Alpine `<select>` caveat.
    Scope: `row`, `pkg`, `$rowType`.
--}}
<template x-if="row.type === @js($rowType->getName())">
    <div class="fi-pc-select-cell">
        <select
            class="fi-pc-input"
            x-model="row.values[pkg.id]"
            x-show="row.config.options.length"
            @disabled($isDisabled)
        >
            <option value="">
                {{ __('filament-advanced-components::package-comparison.select_empty_option') }}
            </option>

            <template x-for="option in row.config.options" x-bind:key="option">
                <option
                    x-bind:value="option"
                    x-text="option"
                    x-bind:selected="row.values[pkg.id] === option"
                ></option>
            </template>
        </select>

        <p class="fi-pc-cell-hint" x-show="! row.config.options.length">
            {{ __('filament-advanced-components::package-comparison.no_options') }}
        </p>
    </div>
</template>
