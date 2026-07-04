{{-- Included / not included checkmark. Scope: `row`, `pkg`, `$rowType`. --}}
<template x-if="row.type === @js($rowType->getName())">
    <label class="fi-pc-boolean-cell">
        <input
            type="checkbox"
            class="fi-pc-checkbox"
            x-model="row.values[pkg.id]"
            @disabled($isDisabled)
        />
    </label>
</template>
