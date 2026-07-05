<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums;

use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\OtpInput;

/**
 * The per-cell size of an
 * {@see OtpInput}.
 *
 * Purely a presentation concern: the value becomes a
 * `fi-otp-input-size-{value}` class on the root, and the stylesheet turns it
 * into concrete cell dimensions and typography. `Compact` and `Large` are
 * the shorthands named in the component's fluent API.
 */
enum OtpSize: string
{
    case Compact = 'compact';

    case Small = 'sm';

    case Medium = 'md';

    case Large = 'lg';
}
