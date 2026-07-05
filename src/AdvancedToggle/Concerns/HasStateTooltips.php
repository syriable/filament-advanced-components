<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns;

use Closure;

/**
 * A tooltip describing what *clicking* the toggle will do next — naturally
 * the opposite state's wording, since the tooltip previews the action, not
 * the current value:
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->onTooltip('Click to disable')
 *     ->offTooltip('Click to enable');
 * ```
 */
trait HasStateTooltips
{
    protected string | Closure | null $onTooltip = null;

    protected string | Closure | null $offTooltip = null;

    public function onTooltip(string | Closure | null $tooltip): static
    {
        $this->onTooltip = $tooltip;

        return $this;
    }

    public function offTooltip(string | Closure | null $tooltip): static
    {
        $this->offTooltip = $tooltip;

        return $this;
    }

    public function getOnTooltip(): ?string
    {
        return $this->evaluate($this->onTooltip);
    }

    public function getOffTooltip(): ?string
    {
        return $this->evaluate($this->offTooltip);
    }

    /**
     * The tooltip for the current state (shown while it's active, describing
     * what clicking it will do).
     */
    public function getStateTooltip(bool $state): ?string
    {
        return $state ? $this->getOnTooltip() : $this->getOffTooltip();
    }

    public function hasStateTooltips(): bool
    {
        return filled($this->onTooltip) || filled($this->offTooltip);
    }
}
