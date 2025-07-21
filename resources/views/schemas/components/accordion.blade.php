@php
    $afterHeader = $getChildSchema($schemaComponent::AFTER_HEADER_SCHEMA_KEY)?->toHtmlString();
    $isAside = $isAside();
    $isCollapsed = $isCollapsed();
    $isCollapsible = $isCollapsible();
    $isCompact = $isCompact();
    $isContained = $isContained();
    $isDivided = $isDivided();
    $isFormBefore = $isFormBefore();
    $description = $getDescription();
    $footer = $getChildSchema($schemaComponent::FOOTER_SCHEMA_KEY)?->toHtmlString();
    $heading = $getHeading();
    $headingTag = $getHeadingTag();
    $icon = $getIcon();
    $iconColor = $getIconColor();
    $iconSize = $getIconSize();
    $shouldPersistCollapsed = $shouldPersistCollapsed();
    $isSecondary = $isSecondary();
@endphp

<div
    {{ $attributes->merge(
            [
                'id' => $getId(),
            ],
            escape: false,
        )->merge($getExtraAttributes(), escape: false)->merge($getExtraAlpineAttributes(), escape: false)->class(['fi-sc-section']) }}>
    @if (filled($label = $getLabel()))
        <div class="fi-sc-section-label-ctn">
            {{ $getChildSchema($schemaComponent::BEFORE_LABEL_SCHEMA_KEY) }}

            <div class="fi-sc-section-label">
                {{ $label }}
            </div>

            {{ $getChildSchema($schemaComponent::AFTER_LABEL_SCHEMA_KEY) }}
        </div>
    @endif

    @if ($aboveContentContainer = $getChildSchema($schemaComponent::ABOVE_CONTENT_SCHEMA_KEY)?->toHtmlString())
        {{ $aboveContentContainer }}
    @endif

    {{-- <x-filament::section class="bg-transparent border-none shadow-none rounded-none ring-0" :after-header="$afterHeader"
        :aside="$isAside" :collapsed="$isCollapsed" :collapsible="$isCollapsible && !$isAside" :compact="$isCompact" :contained="$isContained" :content-before="$isFormBefore"
        :description="$description" :divided="$isDivided" :footer="$footer" :has-content-el="false" :heading="$heading" :heading-tag="$headingTag"
        :icon="$icon" :icon-color="$iconColor" :icon-size="$iconSize" :persist-collapsed="$shouldPersistCollapsed" :secondary="$isSecondary">
        {{ $getChildSchema()->gap(!$isDivided)->extraAttributes(['class' => 'fi-section-content']) }}
    </x-filament::section> --}}

    <section x-data="{
        isCollapsed: @if ($persistCollapsed) $persist(@js($collapsed)).as(`section-${$el.id}-isCollapsed`) @else @js($collapsed) @endif,
    }"
        @if ($collapsible) x-on:collapse-section.window="if ($event.detail.id == $el.id) isCollapsed = true"
            x-on:expand="isCollapsed = false"
            x-on:open-section.window="if ($event.detail.id == $el.id) isCollapsed = false"
            x-on:toggle-section.window="if ($event.detail.id == $el.id) isCollapsed = ! isCollapsed"
            x-bind:class="isCollapsed && 'fi-collapsed'" @endif
        {{ $attributes->class([
            'fi-section',
            'fi-section-not-contained' => !$contained,
            'fi-collapsible' => $collapsible,
            'fi-divided' => $divided,
            // 'fi-secondary' => $secondary,
        ]) }}>

        <header @if ($collapsible) x-on:click="isCollapsed = ! isCollapsed" @endif
            class="fi-section-header items-center px-6 py-2.5 bg-gray-100  dark:bg-gray-700"
            :class="{ 'rounded-xl': isCollapsed, 'rounded-t-xl': !isCollapsed }">
            <div class="fi-section-header-text-ctn">
                @if ($heading)
                    <div class="fi-section-header-heading">
                        {{ $heading }}
                    </div>
                @endif

                @if ($description)
                    <p class="fi-section-header-description">
                        {{ $description }}
                    </p>
                @endif

            </div>

            @if ($collapsible)
                <span class="transition duration-75" :class="{ '-rotate-180': !isCollapsed }">
                    <x-filament::icon-button color="gray" :icon="\Filament\Support\Icons\Heroicon::ChevronUp" :icon-alias="\Filament\Support\View\SupportIconAlias::SECTION_COLLAPSE_BUTTON"
                        x-on:click.stop="isCollapsed = ! isCollapsed" class="fi-section-collapse-btn" />
                </span>
            @endif
        </header>

        <div @if ($collapsible) x-bind:aria-expanded="(! isCollapsed).toString()" @if ($collapsed || $persistCollapsed)  x-cloak @endif
            @endif
            class="fi-section-content-ctn" >
            {{ $getChildSchema()->gap(!$isDivided)->extraAttributes(['class' => 'fi-section-content']) }}
            @if ($belowContentContainer = $getChildSchema($schemaComponent::BELOW_CONTENT_SCHEMA_KEY)?->toHtmlString())
                {{ $belowContentContainer }}
            @endif

        </div>
    </section>
</div>
