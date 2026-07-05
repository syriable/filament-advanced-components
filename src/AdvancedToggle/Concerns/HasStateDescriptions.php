<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns;

use Closure;

/**
 * A contextual message rendered under the toggle, describing what the
 * *current* state means for the user:
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->onDescription('Users can access this feature.')
 *     ->offDescription('Feature is disabled.');
 * ```
 */
trait HasStateDescriptions
{
    protected string | Closure | null $onDescription = null;

    protected string | Closure | null $offDescription = null;

    public function onDescription(string | Closure | null $description): static
    {
        $this->onDescription = $description;

        return $this;
    }

    public function offDescription(string | Closure | null $description): static
    {
        $this->offDescription = $description;

        return $this;
    }

    public function getOnDescription(): ?string
    {
        return $this->evaluate($this->onDescription);
    }

    public function getOffDescription(): ?string
    {
        return $this->evaluate($this->offDescription);
    }

    /**
     * The description for whichever state is currently active.
     */
    public function getStateDescription(bool $state): ?string
    {
        return $state ? $this->getOnDescription() : $this->getOffDescription();
    }

    public function hasStateDescriptions(): bool
    {
        return filled($this->onDescription) || filled($this->offDescription);
    }
}
