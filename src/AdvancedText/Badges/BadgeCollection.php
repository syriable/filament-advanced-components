<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges;

use Filament\Support\Components\ViewComponent;
use Illuminate\Support\Collection;

/**
 * A typed collection of {@see AdvancedBadge} definitions.
 *
 * @extends Collection<int, AdvancedBadge>
 */
class BadgeCollection extends Collection
{
    /**
     * @param  array<int, AdvancedBadge>  $items
     */
    final public function __construct($items = [])
    {
        parent::__construct($items);
    }

    /**
     * Normalize a badge list: plain strings become label-only badges.
     *
     * @param  iterable<int, AdvancedBadge | string>  $badges
     */
    public static function normalize(iterable $badges): static
    {
        $normalized = [];

        foreach ($badges as $badge) {
            $normalized[] = is_string($badge) ? AdvancedBadge::make($badge) : $badge;
        }

        return new static($normalized);
    }

    /**
     * Resolve every badge against the owning component, dropping the ones
     * that are hidden, unauthorized, or empty for this cell.
     *
     * @return array<BadgeViewModel>
     */
    public function resolveFor(ViewComponent $component): array
    {
        $viewModels = [];

        foreach ($this->all() as $badge) {
            if ($viewModel = $badge->resolve($component)) {
                $viewModels[] = $viewModel;
            }
        }

        return $viewModels;
    }
}
