<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\AdvancedBadge;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\BadgeCollection;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\BadgeViewModel;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\RendersBadges;

/**
 * Attaches independent {@see AdvancedBadge} definitions to the component,
 * rendered after its content.
 *
 * ```php
 * AdvancedTextColumn::make('status')
 *     ->badges([
 *         AdvancedBadge::make('Verified')->color('success')->border()->pulse(),
 *         AdvancedBadge::make('Premium')->color('warning')->bounce(),
 *     ]);
 * ```
 */
trait HasBadges
{
    /**
     * @var array<int, AdvancedBadge | string> | Closure
     */
    protected array | Closure $advancedBadges = [];

    /**
     * Attach one or many badges. Accepts badge builders, plain strings
     * (which become label-only badges), or a closure returning either.
     *
     * @param  array<int, AdvancedBadge | string> | Closure  $badges
     */
    public function badges(array | Closure $badges): static
    {
        $this->advancedBadges = $badges;

        return $this;
    }

    public function hasAdvancedBadges(): bool
    {
        return $this->advancedBadges instanceof Closure || $this->advancedBadges !== [];
    }

    public function getAdvancedBadges(): BadgeCollection
    {
        return BadgeCollection::normalize($this->evaluate($this->advancedBadges) ?? []);
    }

    /**
     * The evaluated, render-ready badges for the current cell.
     *
     * @return array<BadgeViewModel>
     */
    public function getAdvancedBadgeViewModels(): array
    {
        return $this->getAdvancedBadges()->resolveFor($this);
    }

    protected function generateAdvancedBadgesHtml(): string
    {
        return app(RendersBadges::class)->renderCollection($this->getAdvancedBadgeViewModels());
    }
}
