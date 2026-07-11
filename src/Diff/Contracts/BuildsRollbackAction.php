<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\Contracts;

use Filament\Actions\Action;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Contracts\BuildsConfirmationAction;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\DiffField;

/**
 * Turns a {@see DiffField}'s registered {@see DiffField::onRollback()}
 * callback into the real modal submit button configuration.
 *
 * `$action` is the {@see Action} Filament already builds and wires up as the
 * modal's submit button (label, color, and — crucially — the click handler
 * that actually invokes the mounted action through Livewire); this method
 * customizes and returns that same instance rather than replacing it, so the
 * button keeps working. Rebind this in a service provider to change how
 * every Rollback button in the app looks or behaves, without subclassing
 * {@see DiffField} itself — the same extension point the package uses for
 * {@see BuildsConfirmationAction}.
 */
interface BuildsRollbackAction
{
    public function build(DiffField $field, Action $action): Action;
}
