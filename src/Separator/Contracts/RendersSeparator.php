<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Contracts;

use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Rendering\OptionRenderer;
use Syriable\Filament\Plugins\AdvancedComponents\Schemas\Components\Separator;

/**
 * Turns a fully configured {@see Separator} into HTML.
 *
 * Rebind this in your own service provider to change how every separator in
 * the app renders — swap the Blade view, skip Blade entirely in favor of a
 * hand-built string (as {@see OptionRenderer}
 * does for select options), or delegate to a design system's own divider
 * component — without touching the package source or the `Separator` class
 * itself.
 */
interface RendersSeparator
{
    public function render(Separator $separator): string;
}
