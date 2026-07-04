{{--
    PackageComparison — a Fiverr-style package comparison editor.

    Fields (unlike table columns and infolist entries) render their own
    wrapper, so this view mounts the field wrapper explicitly and puts the
    Alpine root inside it. The server renders no cells at all: Alpine stamps
    the table client-side from the entangled JSON state, and every allowed row
    type contributes one `<template x-if>` block (see the cells/ partials)
    that is cloned per row × package intersection.
--}}

@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $rowTypes = $getRowTypes();
    $isCollapsible = $isCollapsible();
    $isFeatureColumnSticky = $isFeatureColumnSticky();
    $isPackageReorderingAllowed = $isPackageReorderingAllowed();
    $isFeatureReorderingAllowed = $isFeatureReorderingAllowed();
    $arePackagesAddable = $arePackagesAddable();
    $arePackagesDeletable = $arePackagesDeletable();
    $arePackagesRenameable = $arePackagesRenameable();
    $areFeaturesAddable = $areFeaturesAddable();
    $areFeaturesDeletable = $areFeaturesDeletable();
    $reorderAnimationDuration = $getReorderAnimationDuration();
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    class="fi-fo-package-comparison-wrp"
>
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('package-comparison', 'syriable/filament-advanced-components') }}"
        x-data="packageComparison({
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                    isDisabled: @js($isDisabled),
                    minPackages: @js($getMinPackages()),
                    maxPackages: @js($getMaxPackages()),
                    canAddPackages: @js($arePackagesAddable),
                    canDeletePackages: @js($arePackagesDeletable),
                    types: @js($getRowTypeDescriptors()),
                    packageTitleTemplate: @js(__('filament-advanced-components::package-comparison.package_default_title', ['letter' => ':letter'])),
                    summaryLabels: @js(__('filament-advanced-components::package-comparison.summary')),
                    unitLabels: @js(__('filament-advanced-components::package-comparison.units')),
                })"
        wire:ignore
        wire:key="{{ $getLivewireKey() }}.{{
            substr(md5(serialize([
                $isDisabled,
            ])), 0, 64)
        }}"
        {{
            $attributes
                ->merge($getExtraAlpineAttributes(), escape: false)
                ->class([
                    'fi-pc',
                    'fi-pc-sticky-feature-col' => $isFeatureColumnSticky,
                    'fi-disabled' => $isDisabled,
                ])
        }}
        style="--fi-pc-feature-col-w: {{ $getFeatureColumnWidth() }}; --fi-pc-package-col-min-w: {{ $getPackageColumnMinWidth() }};"
    >
        @include('filament-advanced-components::components.package-comparison.toolbar')

        <div
            class="fi-pc-scroll-ctn"
            x-show="! collapsed"
            x-bind:class="{ 'fi-pc-settings-open': settingsRowId !== null }"
        >
            <div class="fi-pc-table" role="table">
                @include('filament-advanced-components::components.package-comparison.header')

                <div
                    class="fi-pc-body"
                    @if ($isFeatureReorderingAllowed)
                        x-sortable
                        x-on:end.stop="reorderRows($event)"
                        data-sortable-animation-duration="{{ $reorderAnimationDuration }}"
                    @endif
                >
                    <template
                        x-for="row in state.rows"
                        x-bind:key="row.id"
                    >
                        @include('filament-advanced-components::components.package-comparison.row')
                    </template>
                </div>

                <p class="fi-pc-empty" x-show="! state.rows.length">
                    {{ __('filament-advanced-components::package-comparison.empty') }}
                </p>
            </div>
        </div>
    </div>
</x-dynamic-component>
