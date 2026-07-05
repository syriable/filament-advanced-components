<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Rendering;

use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedToggle;

/**
 * The fully evaluated, render-ready state of an
 * {@see AdvancedToggle}.
 *
 * Every lazy value — including which direction(s) currently require
 * confirmation — is resolved once by the time this object exists, so the
 * Blade view stays a dumb template and the client only ever needs two
 * booleans ({@see $requiresConfirmationWhenTurningOn} /
 * {@see $requiresConfirmationWhenTurningOff}) to decide whether to intercept
 * a click, without re-deriving any configuration in JavaScript.
 */
readonly class ToggleViewModel
{
    public function __construct(
        public bool $state,
        public bool $hasConfirmation,
        public bool $requiresConfirmationWhenTurningOn,
        public bool $requiresConfirmationWhenTurningOff,
        public string $confirmationActionName,
        public ?string $mountKey,
        public ?string $stateLabel,
        public ?string $stateDescription,
        public ?string $stateTooltip,
        public ?string $badgeLabel,
        public string | array | null $badgeColor,
        public bool $isAnimated,
        public int $animationDuration,
        public ?string $disabledReason,
    ) {}

    /**
     * Whether the client needs to intercept clicks at all — false when
     * confirmation isn't configured, or is configured but resolves to `false`
     * for both directions on this render.
     */
    public function interceptsClicks(): bool
    {
        return $this->hasConfirmation
            && ($this->requiresConfirmationWhenTurningOn || $this->requiresConfirmationWhenTurningOff);
    }

    public function hasMeta(): bool
    {
        return filled($this->stateDescription) || filled($this->badgeLabel) || filled($this->disabledReason);
    }

    /**
     * The package's own animated elements' transition duration, as a CSS
     * value — `0s` when {@see $isAnimated} is false, so the stylesheet's
     * transitions collapse without extra selectors.
     */
    public function animationDurationCss(): string
    {
        return $this->isAnimated ? "{$this->animationDuration}ms" : '0s';
    }
}
