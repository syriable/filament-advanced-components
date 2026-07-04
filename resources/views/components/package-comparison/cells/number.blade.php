{{-- Numeric value. Scope: `row`, `pkg`, `$rowType`. --}}
<template x-if="row.type === @js($rowType->getName())">
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
</template>
