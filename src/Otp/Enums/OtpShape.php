<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums;

use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\OtpInput;

/**
 * The corner style of an
 * {@see OtpInput}'s
 * cells — `rounded` (the default) or `square`. Becomes a
 * `fi-otp-input-shape-{value}` class on the root.
 */
enum OtpShape: string
{
    case Rounded = 'rounded';

    case Square = 'square';
}
