{{--
    Composite delivery time: amount + unit (`{"amount": 7, "unit": "days"}`).
    Scope: `row`, `pkg`, `$rowType`.
--}}
<template x-if="row.type === @js($rowType->getName())">
    <div class="fi-pc-delivery-cell">
        <input
            type="number"
            min="0"
            class="fi-pc-input"
            x-model.number="row.values[pkg.id].amount"
            @disabled($isDisabled)
            placeholder="0"
            aria-label="{{ __('filament-advanced-components::package-comparison.delivery_amount') }}"
        />

        <select
            class="fi-pc-input"
            x-model="row.values[pkg.id].unit"
            @disabled($isDisabled)
            aria-label="{{ __('filament-advanced-components::package-comparison.delivery_unit') }}"
        >
            <template x-for="unit in row.config.units" x-bind:key="unit">
                <option
                    x-bind:value="unit"
                    x-text="unitLabel(unit)"
                    x-bind:selected="row.values[pkg.id].unit === unit"
                ></option>
            </template>
        </select>
    </div>
</template>
