<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns;

use Closure;

/**
 * Browser one-time-code autofill. When enabled, the first cell is given
 * `autocomplete="one-time-code"` so the OS/browser can offer the SMS or
 * authenticator code; the Alpine layer then spreads the delivered value
 * across the remaining cells exactly as a paste would.
 */
trait HasAutocomplete
{
    protected bool | Closure $hasAutocomplete = false;

    public function autocomplete(bool | Closure $condition = true): static
    {
        $this->hasAutocomplete = $condition;

        return $this;
    }

    public function hasAutocomplete(): bool
    {
        return (bool) $this->evaluate($this->hasAutocomplete);
    }
}
