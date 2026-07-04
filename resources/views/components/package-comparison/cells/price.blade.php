{{-- Price with the row's currency as a prefix. Scope: `row`, `pkg`, `$rowType`. --}}
<template x-if="row.type === @js($rowType->getName())">
    <div class="fi-pc-price-cell">
        <span class="fi-pc-price-currency" x-text="row.config.currency"></span>

        <input
            type="number"
            class="fi-pc-input"
            x-model.number="row.values[pkg.id]"
            x-bind:min="row.config.min"
            x-bind:max="row.config.max"
            x-bind:step="row.config.step"
            @disabled($isDisabled)
            placeholder="0"
        />
    </div>
</template>
