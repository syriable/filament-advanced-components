<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns;

use Closure;

/**
 * Fires once every cell is filled.
 *
 *  - `autoSubmit()` (or a truthy value) dispatches a cancelable
 *    `otp-completed` browser event carrying the value, which the surrounding
 *    Livewire component can listen for (`x-on:otp-completed`) to submit its
 *    form — the least surprising default, since a Filament schema has no
 *    single universal "submit".
 *  - `autoSubmit('verify')` additionally calls the `verify` Livewire method
 *    directly (`$wire.verify()`), for the common "submit immediately" case
 *    with no wiring.
 *
 * Either way the event still fires, so custom callbacks remain possible
 * alongside a named action.
 */
trait HasAutoSubmit
{
    protected bool | string | Closure $autoSubmit = false;

    public function autoSubmit(bool | string | Closure $condition = true): static
    {
        $this->autoSubmit = $condition;

        return $this;
    }

    public function shouldAutoSubmit(): bool
    {
        $value = $this->evaluate($this->autoSubmit);

        return $value !== false && $value !== null;
    }

    /**
     * The Livewire method to call on completion, or null to only dispatch
     * the `otp-completed` event.
     */
    public function getAutoSubmitAction(): ?string
    {
        $value = $this->evaluate($this->autoSubmit);

        return is_string($value) && filled($value) ? $value : null;
    }
}
