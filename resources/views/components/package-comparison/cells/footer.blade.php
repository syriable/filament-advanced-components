{{--
    Per-package footer line (call-to-action / footnote), visually anchored
    like the bottom row of Fiverr's table. Scope: `row`, `pkg`, `$rowType`.
--}}
<template x-if="row.type === @js($rowType->getName())">
    <input
        type="text"
        class="fi-pc-input fi-pc-footer-input"
        x-model="row.values[pkg.id]"
        @disabled($isDisabled)
        placeholder="{{ __('filament-advanced-components::package-comparison.footer_placeholder') }}"
    />
</template>
