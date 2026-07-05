<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns;

use Closure;

/**
 * An optional badge rendered next to the switch, one per state:
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->onBadge('Enabled', 'success')
 *     ->offBadge('Disabled', 'gray');
 * ```
 *
 * Since the label and color both accept closures, a badge isn't limited to
 * describing the raw boolean — it can reflect any related status:
 *
 * ```php
 * ->offBadge(fn ($record) => $record->archived_at ? 'Archived' : 'Disabled')
 * ```
 */
trait HasStateBadge
{
    protected string | Closure | null $onBadge = null;

    protected string | Closure | null $offBadge = null;

    protected string | array | Closure | null $onBadgeColor = 'success';

    protected string | array | Closure | null $offBadgeColor = 'gray';

    public function onBadge(string | Closure | null $label, string | array | Closure | null $color = null): static
    {
        $this->onBadge = $label;

        if ($color !== null) {
            $this->onBadgeColor = $color;
        }

        return $this;
    }

    public function offBadge(string | Closure | null $label, string | array | Closure | null $color = null): static
    {
        $this->offBadge = $label;

        if ($color !== null) {
            $this->offBadgeColor = $color;
        }

        return $this;
    }

    public function getOnBadge(): ?string
    {
        return $this->evaluate($this->onBadge);
    }

    public function getOffBadge(): ?string
    {
        return $this->evaluate($this->offBadge);
    }

    /**
     * @return string | array<int, string> | null
     */
    public function getOnBadgeColor(): string | array | null
    {
        return $this->evaluate($this->onBadgeColor);
    }

    /**
     * @return string | array<int, string> | null
     */
    public function getOffBadgeColor(): string | array | null
    {
        return $this->evaluate($this->offBadgeColor);
    }

    public function getStateBadge(bool $state): ?string
    {
        return $state ? $this->getOnBadge() : $this->getOffBadge();
    }

    /**
     * @return string | array<int, string> | null
     */
    public function getStateBadgeColor(bool $state): string | array | null
    {
        return $state ? $this->getOnBadgeColor() : $this->getOffBadgeColor();
    }

    public function hasStateBadge(): bool
    {
        return filled($this->onBadge) || filled($this->offBadge);
    }
}
