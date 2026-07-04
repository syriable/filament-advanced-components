{{--
    One feature row, stamped by Alpine per entry in `state.rows`. The sticky
    feature cell carries the drag handle, the inline label input, the type
    badge, the per-type settings popover, and the delete button; then one
    cell per package is stamped, and each allowed row type contributes a
    `<template x-if="row.type === '…'">` block via its cell view, so exactly
    one editor renders per cell.

    Scope inherited by the cell/settings partials: `row`, `pkg`, plus the
    variables computed in the root view's `@php` block.
--}}

<div
    class="fi-pc-row"
    role="row"
    x-bind:style="gridStyle"
    x-bind:x-sortable-item="row.id"
    x-bind:class="{ 'fi-pc-row-settings-open': settingsRowId === row.id }"
>
    <div class="fi-pc-feature-cell" role="rowheader">
        @if ($isFeatureReorderingAllowed)
            <button
                type="button"
                class="fi-pc-icon-btn fi-pc-handle"
                x-sortable-handle
                aria-label="{{ __('filament-advanced-components::package-comparison.actions.reorder_feature') }}"
            >
                <x-filament::icon icon="heroicon-m-bars-2" class="fi-pc-icon" />
            </button>
        @endif

        <div class="fi-pc-feature-main">
            <input
                type="text"
                class="fi-pc-feature-label"
                x-model="row.label"
                @disabled($isDisabled)
                placeholder="{{ __('filament-advanced-components::package-comparison.feature_label_placeholder') }}"
                aria-label="{{ __('filament-advanced-components::package-comparison.feature_label_placeholder') }}"
            />

            <span class="fi-pc-type-badge" x-text="typeLabel(row.type)"></span>
        </div>

        @unless ($isDisabled)
            <div class="fi-pc-feature-actions">
                <button
                    type="button"
                    class="fi-pc-icon-btn"
                    x-show="hasSettings(row)"
                    x-on:click="toggleSettings(row.id)"
                    x-bind:aria-expanded="(settingsRowId === row.id).toString()"
                    aria-label="{{ __('filament-advanced-components::package-comparison.actions.feature_settings') }}"
                >
                    <x-filament::icon icon="heroicon-m-cog-6-tooth" class="fi-pc-icon" />
                </button>

                @if ($areFeaturesDeletable)
                    <button
                        type="button"
                        class="fi-pc-icon-btn fi-pc-danger"
                        x-on:click="removeRow(row.id)"
                        aria-label="{{ __('filament-advanced-components::package-comparison.actions.remove_feature') }}"
                    >
                        <x-filament::icon icon="heroicon-m-trash" class="fi-pc-icon" />
                    </button>
                @endif
            </div>

            <div
                class="fi-pc-settings-panel"
                x-cloak
                x-show="settingsRowId === row.id"
                x-transition.origin.top.left
                x-on:click.outside="if (settingsRowId === row.id) settingsRowId = null"
            >
                @foreach ($rowTypes as $rowType)
                    @if ($rowType->getSettingsView() !== null)
                        @include($rowType->getSettingsView())
                    @endif
                @endforeach
            </div>
        @endunless
    </div>

    <template
        x-for="pkg in state.packages"
        x-bind:key="pkg.id"
    >
        <div class="fi-pc-cell" role="cell">
            @foreach ($rowTypes as $rowType)
                @include($rowType->getCellView())
            @endforeach
        </div>
    </template>
</div>
