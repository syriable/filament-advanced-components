<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Confirmation;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasConfirmation;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasConfirmationForm;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Contracts\BuildsConfirmationAction;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedToggle;
use Throwable;

/**
 * Builds the {@see Action} that backs
 * {@see HasConfirmation::requiresConfirmation()},
 * translating the field's confirmation configuration into native Filament
 * action-modal configuration — no bespoke modal markup exists anywhere in
 * this package.
 *
 * ## Pipeline
 *
 * The "detect intent → keep state → confirm → validate → commit" flow
 * described by the field's documentation maps directly onto Filament's own
 * mounted-action lifecycle, so this class does not reimplement any of it:
 *
 *  - **Detect intent / keep state unchanged** — handled client-side, before
 *    any of this ever runs (see `advanced-toggle.blade.php`): the switch's
 *    entangled value is never written to until the action call below
 *    succeeds.
 *  - **Open modal / wait for interaction** — Filament's own
 *    `mountAction()`/action-modal rendering.
 *  - **Validation** — Filament's own schema validation, run automatically
 *    against {@see HasConfirmationForm::confirmationSchema()}
 *    before {@see buildConfirmationClosure()} is ever invoked.
 *  - **Commit** — {@see buildConfirmationClosure()}, below.
 */
class ConfirmationManager implements BuildsConfirmationAction
{
    public function build(AdvancedToggle $toggle): Action
    {
        return Action::make($toggle->getConfirmationActionName())
            ->label($toggle->getLabel())
            ->requiresConfirmation()
            ->modalHeading(fn (): string | Htmlable | null => $toggle->getConfirmationHeading())
            ->modalDescription(fn (): string | Htmlable | null => $toggle->getConfirmationDescription())
            ->modalIcon(fn (): mixed => $toggle->getConfirmationIcon())
            ->modalIconColor(fn (): string | array | null => $toggle->getConfirmationIconColor())
            ->modalWidth(fn (): mixed => $toggle->getConfirmationWidth())
            ->modalAlignment(fn (): mixed => $toggle->getConfirmationAlignment())
            ->modalSubmitActionLabel(fn (): ?string => $toggle->getConfirmationConfirmButtonLabel())
            ->modalCancelActionLabel(fn (): ?string => $toggle->getConfirmationCancelButtonLabel())
            ->modalSubmitAction(function (Action $action) use ($toggle): Action {
                if (filled($color = $toggle->getConfirmationConfirmButtonColor())) {
                    $action->color($color);
                }

                return $action;
            })
            ->modalCancelAction($this->buildCancelActionModifier($toggle))
            ->schema($toggle->getConfirmationSchema())
            ->disabled(fn (): bool => $toggle->isDisabled())
            ->action($this->buildConfirmationClosure());
    }

    /**
     * The main confirmation callback: runs the developer's
     * {@see HasConfirmation::onConfirm()}
     * callback, commits the new state only if it doesn't throw, then fires
     * {@see HasConfirmation::afterConfirmed()}.
     *
     * Parameters are injected by Filament's own evaluator — `$component` by
     * name resolves to the schema component the action is registered on
     * (the toggle itself), and `$action` (typed) resolves to this very
     * mounted action instance, exactly like Filament's own field-hosted
     * actions (e.g. `Repeater`'s add/delete/reorder actions).
     */
    protected function buildConfirmationClosure(): Closure
    {
        return function (array $arguments, array $data, AdvancedToggle $component, Action $action): void {
            $oldState = (bool) $component->getState();
            $newState = (bool) ($arguments['state'] ?? ! $oldState);

            try {
                $component->evaluate($component->getOnConfirmCallback(), [
                    'newState' => $newState,
                    'oldState' => $oldState,
                    'state' => $newState,
                    'data' => $data,
                ]);
            } catch (Halt | Cancel $exception) {
                // A developer deliberately using Filament's own control-flow
                // signals inside onConfirm() gets their native behavior
                // untouched, rather than being wrapped in a failure
                // notification.
                throw $exception;
            } catch (Throwable $exception) {
                $this->handleConfirmationFailure($component, $action, $exception, $oldState, $newState, $data);

                return;
            }

            $component->state($newState);
            $component->callAfterStateUpdated();

            $component->evaluate($component->getAfterConfirmedCallback(), [
                'newState' => $newState,
                'oldState' => $oldState,
                'state' => $newState,
                'data' => $data,
            ]);
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleConfirmationFailure(
        AdvancedToggle $component,
        Action $action,
        Throwable $exception,
        bool $oldState,
        bool $newState,
        array $data,
    ): void {
        $notification = $component->getConfirmationFailureNotification($exception, $oldState, $newState, $data);

        if (filled($notification?->getTitle())) {
            $notification->send();
        }

        // Both branches roll back any database writes the `onConfirm`
        // callback made before throwing. `halt()` keeps the modal open
        // (the default — the user sees the notification and can retry
        // without re-triggering the toggle); `cancel()` dismisses it. The
        // toggle's own state is untouched either way, since `state()` above
        // is never reached.
        if ($component->shouldKeepConfirmationModalOpenOnFailure()) {
            $action->halt(shouldRollBackDatabaseTransaction: true);
        }

        $action->cancel(shouldRollBackDatabaseTransaction: true);
    }

    /**
     * The cancel button's color applies unconditionally; a real server
     * round-trip is only wired up when the developer registered an
     * {@see HasConfirmation::onCancel()}
     * callback — otherwise it keeps Filament's native, instant client-side
     * `close()`, which never touches the server at all.
     */
    protected function buildCancelActionModifier(AdvancedToggle $toggle): Closure
    {
        return function (Action $action) use ($toggle): Action {
            if (filled($color = $toggle->getConfirmationCancelButtonColor())) {
                $action->color($color);
            }

            $onCancel = $toggle->getOnCancelCallback();

            if (! $onCancel instanceof Closure) {
                return $action;
            }

            return $action
                ->close(false)
                ->action(function (AdvancedToggle $component) use ($onCancel): void {
                    $oldState = (bool) $component->getState();

                    $component->evaluate($onCancel, [
                        'oldState' => $oldState,
                        'state' => $oldState,
                    ]);
                });
        };
    }
}
