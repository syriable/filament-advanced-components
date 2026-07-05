<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Support;

use Filament\Support\Facades\FilamentColor;

/**
 * Maps colors expressed as Filament configuration onto concrete CSS values.
 *
 * A registered Filament color name (`primary`, `success`, custom palette
 * names, …) becomes its theme CSS variable; anything else is treated as a
 * literal CSS color and passed through untouched.
 *
 * Filament exposes each registered color's shades at `:root` as bare
 * `--{name}-{shade}` custom properties (see `FilamentAsset`'s asset view,
 * which emits `--{$name}-{$shade}: …` for every color returned by
 * `FilamentColor::getColors()`) — there is no `--color-{name}-{shade}`
 * variant for named colors; that prefix is only ever aliased for `gray`.
 */
final class ColorResolver
{
    private function __construct() {}

    public static function toCss(string $color, int $shade = 600): string
    {
        if (array_key_exists($color, FilamentColor::getColors())) {
            return "var(--{$color}-{$shade})";
        }

        return $color;
    }
}
