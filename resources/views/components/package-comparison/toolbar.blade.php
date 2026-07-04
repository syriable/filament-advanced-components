{{--
    The editor's toolbar: collapse toggle, a live summary of the table, the
    "add feature" type picker (a pure-Alpine popover listing the allowed row
    types, rendered server-side so icons and labels need no JS), and the
    "add package" button.
--}}

<div class="fi-pc-toolbar">
    <div class="fi-pc-toolbar-start">
        @if ($isCollapsible)
            <button
                type="button"
                class="fi-pc-icon-btn"
                x-on:click="collapsed = ! collapsed"
                x-bind:aria-expanded="(! collapsed).toString()"
                aria-label="{{ __('filament-advanced-components::package-comparison.actions.toggle_collapse') }}"
            >
                <x-filament::icon
                    icon="heroicon-m-chevron-down"
                    class="fi-pc-icon fi-pc-collapse-icon"
                    x-bind:class="{ 'fi-pc-collapsed': collapsed }"
                />
            </button>
        @endif

        <span class="fi-pc-summary" x-text="summary"></span>
    </div>

    @unless ($isDisabled)
        <div class="fi-pc-toolbar-end">
            @if ($areFeaturesAddable)
                <div class="fi-pc-dropdown" x-on:keydown.escape.stop="typePickerOpen = false">
                    <button
                        type="button"
                        class="fi-pc-btn"
                        x-on:click="typePickerOpen = ! typePickerOpen"
                        x-bind:aria-expanded="typePickerOpen.toString()"
                    >
                        <x-filament::icon icon="heroicon-m-plus" class="fi-pc-icon" />
                        {{ __('filament-advanced-components::package-comparison.actions.add_feature') }}
                        <x-filament::icon icon="heroicon-m-chevron-down" class="fi-pc-icon" />
                    </button>

                    <div
                        class="fi-pc-dropdown-panel"
                        x-cloak
                        x-show="typePickerOpen"
                        x-transition.origin.top.right
                        x-on:click.outside="typePickerOpen = false"
                    >
                        @foreach ($rowTypes as $rowType)
                            <button
                                type="button"
                                class="fi-pc-dropdown-item"
                                x-on:click="addRow(@js($rowType->getName()))"
                            >
                                <x-filament::icon :icon="$rowType->getIcon()" class="fi-pc-icon" />
                                <span>{{ $rowType->getLabel() }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($arePackagesAddable)
                <button
                    type="button"
                    class="fi-pc-btn fi-pc-btn-primary"
                    x-on:click="addPackage()"
                    x-show="canAddPackage"
                >
                    <x-filament::icon icon="heroicon-m-plus" class="fi-pc-icon" />
                    {{ __('filament-advanced-components::package-comparison.actions.add_package') }}
                </button>
            @endif
        </div>
    @endunless
</div>
