<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Diff\Rollback;

use Filament\Actions\Action;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Contracts\BuildsRollbackAction;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\DiffField;

/**
 * Builds the modal's "Rollback" submit button: danger-colored with an undo
 * icon. `$action` already carries the click wiring Filament set up for the
 * modal's submit button (it calls back into
 * {@see DiffField::getViewDiffAction()}'s own `action()` closure — see
 * {@see DiffField::handleRollback()}), so this only needs to customize its
 * appearance.
 */
final class RollbackManager implements BuildsRollbackAction
{
    public function build(DiffField $field, Action $action): Action
    {
        return $action
            ->label(self::translate('filament-advanced-components::diff-field.rollback'))
            ->color('danger')
            ->icon('heroicon-o-arrow-uturn-left');
    }

    /**
     * __() is typed to allow returning a translation array (for pluralized
     * groups); this key is a plain string, so the array branch never
     * actually happens — this narrows it back to `string` for
     * {@see Action::label()}, which doesn't accept one.
     */
    private static function translate(string $key): string
    {
        $value = __($key);

        return is_string($value) ? $value : $key;
    }
}
