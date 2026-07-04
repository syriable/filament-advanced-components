{{--
    Currency editor for price rows, shown in the row's settings popover.
    Coerced to an uppercase code server-side by `PriceRow::normalizeConfig()`.
    Scope: `row`, `$rowType`.
--}}
<template x-if="row.type === @js($rowType->getName())">
    <div class="fi-pc-settings-body">
        <p class="fi-pc-settings-heading">
            {{ __('filament-advanced-components::package-comparison.settings.currency') }}
        </p>

        <input
            type="text"
            class="fi-pc-input"
            x-model="row.config.currency"
            maxlength="8"
            placeholder="USD"
        />
    </div>
</template>
