<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\Concerns;

use Closure;

/**
 * Purely visual configuration for the `PackageComparison` editor, kept apart
 * from the data rules so appearance never leaks into normalization or
 * validation.
 */
trait HasComparisonAppearance
{
    protected bool | Closure $isCollapsible = false;

    protected bool | Closure $isFeatureColumnSticky = true;

    protected string | Closure $packageColumnMinWidth = '12rem';

    protected string | Closure $featureColumnWidth = '14rem';

    protected int | Closure $reorderAnimationDuration = 300;

    /**
     * Let the user collapse the whole editor down to its toolbar.
     */
    public function collapsible(bool | Closure $condition = true): static
    {
        $this->isCollapsible = $condition;

        return $this;
    }

    /**
     * Keep the feature column pinned while the packages scroll horizontally.
     */
    public function stickyFeatureColumn(bool | Closure $condition = true): static
    {
        $this->isFeatureColumnSticky = $condition;

        return $this;
    }

    public function packageColumnMinWidth(string | Closure $width): static
    {
        $this->packageColumnMinWidth = $width;

        return $this;
    }

    public function featureColumnWidth(string | Closure $width): static
    {
        $this->featureColumnWidth = $width;

        return $this;
    }

    public function reorderAnimationDuration(int | Closure $milliseconds): static
    {
        $this->reorderAnimationDuration = $milliseconds;

        return $this;
    }

    public function isCollapsible(): bool
    {
        return (bool) $this->evaluate($this->isCollapsible);
    }

    public function isFeatureColumnSticky(): bool
    {
        return (bool) $this->evaluate($this->isFeatureColumnSticky);
    }

    public function getPackageColumnMinWidth(): string
    {
        return (string) $this->evaluate($this->packageColumnMinWidth);
    }

    public function getFeatureColumnWidth(): string
    {
        return (string) $this->evaluate($this->featureColumnWidth);
    }

    public function getReorderAnimationDuration(): int
    {
        return (int) $this->evaluate($this->reorderAnimationDuration);
    }
}
