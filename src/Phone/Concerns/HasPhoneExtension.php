<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Closure;

/**
 * Optional support for a dialing extension (`+1 415-555-2671 ext 89`).
 *
 * When enabled, a small extension input sits after the number; its digits ride
 * along in the transport value as an RFC 3966 `;ext=` suffix, so the server
 * parses and preserves the extension through the whole pipeline — validation,
 * normalization (into any format that carries it, like RFC 3966), and display.
 */
trait HasPhoneExtension
{
    protected bool | Closure $enableExtension = false;

    protected int | Closure $maxExtensionLength = 6;

    public function enableExtension(bool | Closure $condition = true): static
    {
        $this->enableExtension = $condition;

        return $this;
    }

    /**
     * Cap the number of extension digits accepted (client and server). Floors
     * at 1.
     */
    public function maxExtensionLength(int | Closure $length): static
    {
        $this->maxExtensionLength = $length;

        return $this;
    }

    public function hasExtension(): bool
    {
        return (bool) $this->evaluate($this->enableExtension);
    }

    public function getMaxExtensionLength(): int
    {
        return max(1, (int) $this->evaluate($this->maxExtensionLength));
    }
}
