<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Forms\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasAnimations;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasConfirmation;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasConfirmationForm;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasDisabledReason;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasStateBadge;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasStateDescriptions;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasStateLabels;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns\HasStateTooltips;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Confirmation\ConfirmationManager;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Contracts\BuildsConfirmationAction;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Rendering\ToggleViewModel;

/**
 * A drop-in superset of Filament's {@see Toggle} whose core feature is a
 * confirmation modal gating every state change:
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->requiresConfirmation(
 *         title: 'Enable feature',
 *         description: 'Are you sure you want to enable this for everyone?',
 *     )
 *     ->onConfirm(function (bool $newState, bool $oldState, array $data) {
 *         // ...
 *     });
 * ```
 *
 * ## The confirmation guarantee
 *
 * Without `requiresConfirmation()`, an `AdvancedToggle` behaves byte-for-byte
 * like a native `Toggle` — same view, same Alpine wiring, same everything.
 * Calling it changes exactly one thing about how the switch renders: the
 * click is intercepted **before** it ever reaches the entangled Livewire
 * property (see `advanced-toggle.blade.php`), so the visible switch cannot
 * flip, revert, or flicker — it simply doesn't move until a real Filament
 * action call commits the new value via `state()`. There is no "set then
 * undo"; the previous state is never touched unless confirmation succeeds.
 *
 * ## Architecture
 *
 * Every extension point is a small, single-purpose collaborator:
 *
 *  - {@see HasConfirmation} — the confirmation configuration API (condition,
 *    direction scoping, modal appearance, lifecycle callbacks, failure
 *    handling);
 *  - {@see HasConfirmationForm} — {@see confirmationSchema()}, the fully
 *    custom modal form;
 *  - {@see ConfirmationManager} — turns that configuration into a real
 *    {@see Action}, reusing Filament's own action-modal architecture
 *    entirely (see {@see BuildsConfirmationAction} to swap it globally);
 *  - {@see HasStateLabels}, {@see HasStateDescriptions},
 *    {@see HasStateTooltips}, {@see HasStateBadge}, {@see HasDisabledReason},
 *    {@see HasAnimations} — the smaller, independent presentation features;
 *  - {@see ToggleViewModel} — the resolved, render-ready state the Blade view
 *    reads, so no configuration is re-evaluated (or re-derived in
 *    JavaScript) more than once per render.
 *
 * `onColor()`, `offColor()`, `onIcon()`, `offIcon()`, and `inline()` are
 * already native to `Toggle` and need no changes here.
 */
class AdvancedToggle extends Toggle
{
    use HasAnimations;
    use HasConfirmation;
    use HasConfirmationForm;
    use HasDisabledReason;
    use HasStateBadge;
    use HasStateDescriptions;
    use HasStateLabels;
    use HasStateTooltips;

    protected string $view = 'filament-advanced-components::components.advanced-toggle';

    protected ?Action $confirmationAction = null;

    protected ?BuildsConfirmationAction $confirmationActionBuilder = null;

    /**
     * @return array<Action>
     */
    public function getDefaultActions(): array
    {
        if (! $this->hasConfirmation()) {
            return [];
        }

        return [$this->getConfirmationAction()];
    }

    /**
     * The mounted confirmation {@see Action}, built once per instance by the
     * configured {@see BuildsConfirmationAction} service.
     */
    public function getConfirmationAction(): Action
    {
        return $this->confirmationAction ??= $this->getConfirmationActionBuilder()->build($this);
    }

    /**
     * Swap the confirmation action builder for this instance only,
     * overriding the container-bound {@see BuildsConfirmationAction} default.
     */
    public function buildConfirmationActionUsing(BuildsConfirmationAction $builder): static
    {
        $this->confirmationActionBuilder = $builder;

        return $this;
    }

    public function getConfirmationActionBuilder(): BuildsConfirmationAction
    {
        return $this->confirmationActionBuilder ??= app()->bound(BuildsConfirmationAction::class)
            ? app(BuildsConfirmationAction::class)
            : app(ConfirmationManager::class);
    }

    /**
     * The complete, resolved state the Blade view renders from.
     */
    public function getViewModel(): ToggleViewModel
    {
        $state = (bool) $this->getState();

        return new ToggleViewModel(
            state: $state,
            hasConfirmation: $this->hasConfirmation(),
            requiresConfirmationWhenTurningOn: $this->isConfirmationRequiredForNewState(true),
            requiresConfirmationWhenTurningOff: $this->isConfirmationRequiredForNewState(false),
            confirmationActionName: $this->getConfirmationActionName(),
            mountKey: $this->hasConfirmation() ? $this->getKey() : null,
            stateLabel: $this->getStateLabel($state),
            stateDescription: $this->getStateDescription($state),
            stateTooltip: $this->getStateTooltip($state),
            badgeLabel: $this->getStateBadge($state),
            badgeColor: $this->getStateBadgeColor($state),
            isAnimated: $this->isAnimated(),
            animationDuration: $this->getAnimationDuration(),
            disabledReason: $this->getDisabledReason(),
        );
    }
}
