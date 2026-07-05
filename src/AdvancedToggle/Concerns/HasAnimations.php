<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns;

use Closure;

/**
 * Configurable transitions for the package's own additions — the state
 * message/description, badge, and pending (confirming) dimming — layered on
 * top of the native switch, which already animates its own thumb/track:
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->animated()
 *     ->animationDuration(250);
 * ```
 */
trait HasAnimations
{
    protected bool | Closure $isAnimated = true;

    protected int | Closure $animationDuration = 150;

    public function animated(bool | Closure $condition = true): static
    {
        $this->isAnimated = $condition;

        return $this;
    }

    /**
     * The transition duration, in milliseconds, for the package's own
     * animated elements.
     */
    public function animationDuration(int | Closure $milliseconds): static
    {
        $this->animationDuration = $milliseconds;

        return $this;
    }

    public function isAnimated(): bool
    {
        return (bool) $this->evaluate($this->isAnimated);
    }

    public function getAnimationDuration(): int
    {
        return max(0, (int) $this->evaluate($this->animationDuration));
    }
}
