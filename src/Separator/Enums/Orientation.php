<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Enums;

use Syriable\Filament\Plugins\AdvancedComponents\Schemas\Components\Separator;

/**
 * The axis a {@see Separator}
 * is drawn along.
 */
enum Orientation: string
{
    case Horizontal = 'horizontal';

    case Vertical = 'vertical';
}
