<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\View\Components;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
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
 *
 * @see https://www.w3.org/WAI/WCAG21/Understanding/non-text-contrast.html
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

        // Light mode: the track is a light gray wash on a white-ish surface,
        // so walk from the lightest shade down until one contrasts at least
        // 3:1 against `gray-50`.
        ksort($color);

        foreach (array_keys($color) as $shade) {
            if (Color::isNonTextContrastRatioAccessible($gray[50], $color[$shade])) {
                $bg = $shade;

                break;
            }
        }

        // Dark mode: the track sits on a dark gray surface, so start from the
        // darkest shade and walk up until 3:1 contrast is reached.
        krsort($color);

        foreach (array_keys($color) as $shade) {
            if (Color::isNonTextContrastRatioAccessible($gray[800], $color[$shade])) {
                $darkBg = $shade;

                break;
            }
        }

        return [
            'bg' => $bg ?? 600,
            'dark:bg' => $darkBg ?? 500,
        ];
    }
}
