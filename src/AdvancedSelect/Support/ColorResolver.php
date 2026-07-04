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
 */
final class ColorResolver
{
    private function __construct() {}

    public static function toCss(string $color, int $shade = 600): string
    {
        if (array_key_exists($color, FilamentColor::getColors())) {
            return "var(--color-{$color}-{$shade})";
        }

        return $color;
    }
}
