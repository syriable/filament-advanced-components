<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Contracts;

use Filament\Actions\Action;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\RendersOptions;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedToggle;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Contracts\RendersSeparator;

/**
 * Turns an {@see AdvancedToggle}'s confirmation configuration into the real
 * {@see Action} that gets registered on the field and mounted client-side.
 *
 * Rebind this in a service provider to change how every confirmation modal
 * in the app is built (e.g. to enforce a house style), without subclassing
 * {@see AdvancedToggle} itself — the same extension point the package uses
 * for {@see RendersOptions}
 * and {@see RendersSeparator}.
 */
interface BuildsConfirmationAction
{
    public function build(AdvancedToggle $toggle): Action;
}
