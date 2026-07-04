{{--
    Multi-line text; also serves `DescriptionRow`, which reuses this view
    with its own `$rowType` (so the `x-if` matches `description` and the
    row config provides a taller default). Scope: `row`, `pkg`, `$rowType`.
--}}
<template x-if="row.type === @js($rowType->getName())">
    <textarea
        class="fi-pc-input fi-pc-textarea"
        x-model="row.values[pkg.id]"
        x-bind:rows="row.config.rows ?? 3"
        @disabled($isDisabled)
        placeholder="{{ __('filament-advanced-components::package-comparison.cell_placeholder') }}"
    ></textarea>
</template>
