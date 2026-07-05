<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Support;

use Filament\Support\Facades\FilamentColor;

/**
 * Maps colors expressed as Filament configuration onto concrete CSS values.
 *
 * Three shapes are accepted, mirroring what Filament's own `HasColor` concern
 * allows:
 *
 * - A registered Filament color name (`primary`, `success`, custom palette
 *   names, …) becomes its theme CSS variable.
 * - A raw `Color` palette array (e.g. `Color::Blue`, or any
 *   `[shade => value]` array) is not registered under any name, so it is
 *   indexed directly for the requested shade instead — the same thing
 *   Filament's own `get_color_css_variables()` helper does for an unregistered
 *   array color.
 * - Anything else is treated as a literal CSS color and passed through
 *   untouched.
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

    /**
     * @param  string | array<int | string, string>  $color
     */
    public static function toCss(string | array $color, int $shade = 600): string
    {
        if (is_array($color)) {
            return $color[$shade] ?? reset($color) ?: '';
        }

        if (array_key_exists($color, FilamentColor::getColors())) {
            return "var(--{$color}-{$shade})";
        }

        return $color;
    }
}
