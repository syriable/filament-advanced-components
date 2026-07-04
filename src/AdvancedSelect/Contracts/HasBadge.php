<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts;

use Illuminate\Contracts\Support\Htmlable;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedSelect;

/**
 * Lets an enum case declare a trailing badge for its {@see AdvancedSelect}
 * option, in the same spirit as Filament's first-party enum contracts
 * (`HasLabel`, `HasIcon`, `HasColor`, `HasDescription`).
 *
 * Filament ships no badge contract of its own, so this one lives in the
 * package. The badge inherits the case's `HasColor` color unless the option
 * overrides it.
 *
 * ```php
 * enum SubscriptionTier: string implements HasColor, HasBadge
 * {
 *     case Free = 'free';
 *     case Pro = 'pro';
 *
 *     public function getColor(): string
 *     {
 *         return $this === self::Pro ? 'success' : 'gray';
 *     }
 *
 *     public function getBadge(): ?string
 *     {
 *         return $this === self::Pro ? 'Popular' : null;
 *     }
 * }
 * ```
 */
interface HasBadge
{
    public function getBadge(): string | Htmlable | null;
}
