<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\View\Components;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\ColorMaps\ComponentColorMap;
use Filament\Support\View\Components\Contracts\HasColor;

/**
 * Registers the multi-progress segment with Filament's color system.
 *
 * Filament v5 resolves component colors through {@see HasColor} "color map"
 * classes: given a palette (e.g. the `success` palette), the map picks the
 * lightest shade that still passes the requested WCAG contrast ratio against
 * the surface it sits on, in both light and dark mode. The resolved shades
 * are turned into `fi-bg-color-{shade}` / `dark:fi-bg-color-{shade}` utility
 * classes (for registered colors) or inline CSS custom properties (for
 * arbitrary palettes), which our stylesheet consumes via `var(--bg)`.
 *
 * Because a progress segment is a non-text UI element, it only needs to meet
 * the WCAG 2.1 AA non-text contrast ratio (3:1) against the track behind it.
 */
class MultiProgressSegmentComponent implements HasColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = FilamentColor::getColor('gray');

        return ComponentColorMap::make($color)
            // Light mode: the track is a light gray wash on a white-ish
            // surface, so contrast is measured against `gray-50`.
            ->slot('bg', surface: $gray[50], minRatio: Color::WCAG_AA_NON_TEXT, fallback: 600)
            // Dark mode: the track sits on a dark gray surface, so start from
            // the darkest shade and walk up until 3:1 contrast is reached.
            ->slot('dark:bg', surface: $gray[800], minRatio: Color::WCAG_AA_NON_TEXT, shouldStartFromDarkest: true, fallback: 500)
            ->get();
    }
}
