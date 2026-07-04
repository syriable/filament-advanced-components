{{-- Single-line text. Scope: `row`, `pkg`, `$rowType`. --}}
<template x-if="row.type === @js($rowType->getName())">
    <input
        type="text"
        class="fi-pc-input"
        x-model="row.values[pkg.id]"
        @disabled($isDisabled)
        placeholder="{{ __('filament-advanced-components::package-comparison.cell_placeholder') }}"
    />
</template>
