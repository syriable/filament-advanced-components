{{--
    Stacked radio group over the row's own options. Each cell gets a unique
    input `name` (row id + package id) so groups never leak across cells.
    Scope: `row`, `pkg`, `$rowType`.
--}}
<template x-if="row.type === @js($rowType->getName())">
    <div class="fi-pc-radio-cell">
        <template x-for="option in row.config.options" x-bind:key="option">
            <label class="fi-pc-radio-option">
                <input
                    type="radio"
                    class="fi-pc-radio"
                    x-bind:name="'fi-pc-' + row.id + '-' + pkg.id"
                    x-bind:value="option"
                    x-model="row.values[pkg.id]"
                    @disabled($isDisabled)
                />
                <span x-text="option"></span>
            </label>
        </template>

        <p class="fi-pc-cell-hint" x-show="! row.config.options.length">
            {{ __('filament-advanced-components::package-comparison.no_options') }}
        </p>
    </div>
</template>
