<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Enums;

use Syriable\Filament\Plugins\AdvancedComponents\Schemas\Components\Separator;

/**
 * The built-in visual treatments for a
 * {@see Separator}'s
 * line.
 *
 * `Default` and `Solid` render identically out of the box — both exist so
 * `->solid()` reads naturally as the explicit counterpart to `->dashed()`
 * and `->dotted()`, while `->default()` reads naturally as "reset to the
 * package default".
 *
 * This is not a closed set: {@see HasVariant::getVariant()} falls back to
 * the raw string for any name that isn't one of these cases, so a consuming
 * app can invent its own variant (e.g. `->variant('brand')`) and style it
 * with a matching `.fi-separator-variant-brand` rule in its own CSS —
 * no package changes required.
 */
enum SeparatorVariant: string
{
    case Default = 'default';

    case Subtle = 'subtle';

    case Muted = 'muted';

    case Solid = 'solid';

    case Dashed = 'dashed';

    case Dotted = 'dotted';

    case Zigzag = 'zigzag';
}
