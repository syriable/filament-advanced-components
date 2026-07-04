<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts;

use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\BadgeRenderer;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\BadgeViewModel;

/**
 * Renders evaluated badges into HTML.
 *
 * The default implementation is
 * {@see BadgeRenderer}.
 * Rebind this contract in the container to change how every advanced badge
 * is rendered.
 */
interface RendersBadges
{
    public function render(BadgeViewModel $badge): string;

    /**
     * @param  iterable<BadgeViewModel>  $badges
     */
    public function renderCollection(iterable $badges): string;
}
