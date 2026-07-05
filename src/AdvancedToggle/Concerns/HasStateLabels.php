<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns;

use Closure;
use Filament\Schemas\Components\Concerns\HasLabel;

/**
 * A label shown next to the switch itself, one per state — distinct from the
 * field's own {@see HasLabel::label()},
 * which never changes with the toggle's value.
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->onLabel('Enabled')
 *     ->offLabel('Disabled');
 * ```
 */
trait HasStateLabels
{
    protected string | Closure | null $onLabel = null;

    protected string | Closure | null $offLabel = null;

    public function onLabel(string | Closure | null $label): static
    {
        $this->onLabel = $label;

        return $this;
    }

    public function offLabel(string | Closure | null $label): static
    {
        $this->offLabel = $label;

        return $this;
    }

    public function getOnLabel(): ?string
    {
        return $this->evaluate($this->onLabel);
    }

    public function getOffLabel(): ?string
    {
        return $this->evaluate($this->offLabel);
    }

    /**
     * The label for whichever state is currently active.
     */
    public function getStateLabel(bool $state): ?string
    {
        return $state ? $this->getOnLabel() : $this->getOffLabel();
    }

    public function hasStateLabels(): bool
    {
        return filled($this->onLabel) || filled($this->offLabel);
    }
}
