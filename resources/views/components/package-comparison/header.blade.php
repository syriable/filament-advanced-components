{{--
    The package header row: one sticky corner cell over the feature column,
    then one header cell per package with a drag handle, an inline rename
    input, and a delete button. Alpine stamps the cells; `x-sortable` (with
    `dataIdAttr` = `x-sortable-item`) provides horizontal drag-to-reorder,
    reported back through `reorderPackages()` as an ordered list of ids.
--}}

<div
    class="fi-pc-header"
    role="row"
    x-bind:style="gridStyle"
    @if ($isPackageReorderingAllowed)
        x-sortable
        x-on:end.stop="reorderPackages($event)"
        data-sortable-animation-duration="{{ $reorderAnimationDuration }}"
    @endif
>
    <div class="fi-pc-feature-cell fi-pc-corner-cell" role="columnheader">
        {{ __('filament-advanced-components::package-comparison.features') }}
    </div>

    <template
        x-for="(pkg, packageIndex) in state.packages"
        x-bind:key="pkg.id"
    >
        <div
            class="fi-pc-package-cell"
            role="columnheader"
            x-bind:x-sortable-item="pkg.id"
        >
            @if ($isPackageReorderingAllowed)
                <button
                    type="button"
                    class="fi-pc-icon-btn fi-pc-handle"
                    x-sortable-handle
                    aria-label="{{ __('filament-advanced-components::package-comparison.actions.reorder_package') }}"
                >
                    <x-filament::icon icon="heroicon-m-bars-2" class="fi-pc-icon" />
                </button>
            @endif

            <input
                type="text"
                class="fi-pc-package-title"
                x-model="pkg.title"
                @disabled($isDisabled || (! $arePackagesRenameable))
                placeholder="{{ __('filament-advanced-components::package-comparison.package_title_placeholder') }}"
                aria-label="{{ __('filament-advanced-components::package-comparison.package_title_placeholder') }}"
            />

            @if ($arePackagesDeletable)
                <button
                    type="button"
                    class="fi-pc-icon-btn fi-pc-danger"
                    x-show="canDeletePackage"
                    x-on:click="removePackage(pkg.id)"
                    aria-label="{{ __('filament-advanced-components::package-comparison.actions.remove_package') }}"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="fi-pc-icon" />
                </button>
            @endif
        </div>
    </template>
</div>
