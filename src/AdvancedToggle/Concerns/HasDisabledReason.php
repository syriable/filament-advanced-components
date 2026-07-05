<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns;

use Closure;

/**
 * An explanation shown under a disabled toggle for *why* it can't be
 * changed — Filament's native `disabled()` otherwise gives no way to
 * communicate the reason to the user:
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->disabled(fn ($record) => $record->is_locked)
 *     ->disabledReason(fn ($record) => $record->is_locked
 *         ? 'Disabled because this record is locked.'
 *         : null);
 * ```
 */
trait HasDisabledReason
{
    protected string | Closure | null $disabledReason = null;

    public function disabledReason(string | Closure | null $reason): static
    {
        $this->disabledReason = $reason;

        return $this;
    }

    /**
     * The reason, only when the field is actually disabled.
     */
    public function getDisabledReason(): ?string
    {
        if (! $this->isDisabled()) {
            return null;
        }

        return $this->evaluate($this->disabledReason);
    }

    public function hasDisabledReason(): bool
    {
        return filled($this->disabledReason);
    }
}
