<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Confirmation\ConfirmationManager;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Contracts\BuildsConfirmationAction;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedToggle;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\ActionsFormLivewireComponent;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\Contact;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

beforeEach(function () {
    View::share('errors', new ViewErrorBag);
    ActionsFormLivewireComponent::$components = null;
});

/**
 * Mount a toggle into a real (record-backed, when given) schema container, so
 * closures behave exactly as they would inside a panel.
 */
function makeToggle(string $name = 'enabled', ?Model $record = null): AdvancedToggle
{
    $toggle = AdvancedToggle::make($name);

    $schema = Schema::make(new SchemaLivewireComponent);

    if ($record !== null) {
        $schema->record($record);
    }

    $toggle->container($schema);

    return $toggle;
}

function renderToggle(AdvancedToggle $toggle): string
{
    return $toggle->toHtml();
}

/**
 * Mount a confirmable toggle inside a real Livewire component that can host
 * and mount its confirmation action, exactly like a Filament panel would.
 */
function mountConfirmable(AdvancedToggle $toggle, bool $initialState = false): Testable
{
    ActionsFormLivewireComponent::$components = [$toggle];

    return Livewire::test(ActionsFormLivewireComponent::class, ['data' => [$toggle->getName() => $initialState]]);
}

// ---------------------------------------------------------------------------
// Native parity
// ---------------------------------------------------------------------------

it('behaves like a native Toggle until requiresConfirmation() is used', function () {
    $toggle = makeToggle();

    expect($toggle->hasConfirmation())->toBeFalse()
        ->and($toggle->getDefaultActions())->toBe([])
        ->and($toggle->getDefaultStateCasts())->not->toBeEmpty(); // inherited boolean cast
});

it('inherits the native onColor/offColor/onIcon/offIcon/inline API untouched', function () {
    $toggle = makeToggle()
        ->onColor('success')
        ->offColor('danger')
        ->onIcon('heroicon-o-check')
        ->offIcon('heroicon-o-x-mark')
        ->inline(false);

    expect($toggle->getOnColor())->toBe('success')
        ->and($toggle->getOffColor())->toBe('danger')
        ->and($toggle->getOnIcon())->toBe('heroicon-o-check')
        ->and($toggle->getOffIcon())->toBe('heroicon-o-x-mark')
        ->and($toggle->isInline())->toBeFalse();
});

it('renders exactly the native control markup when confirmation is not configured', function () {
    $html = renderToggle(makeToggle());

    expect($html)->toContain('fi-fo-toggle')
        ->and($html)->toContain('fi-toggle')
        ->and($html)->not->toContain('fi-advanced-toggle-ctn')
        ->and($html)->not->toContain('mountAction');
});

// ---------------------------------------------------------------------------
// Confirmation configuration
// ---------------------------------------------------------------------------

it('activates confirmation and defaults to requiring it unconditionally', function () {
    $toggle = makeToggle()->requiresConfirmation();

    expect($toggle->hasConfirmation())->toBeTrue()
        ->and($toggle->isConfirmationRequiredForNewState(true))->toBeTrue()
        ->and($toggle->isConfirmationRequiredForNewState(false))->toBeTrue();
});

it('accepts a closure condition, receiving the record', function () {
    $protected = (new Contact)->forceFill(['id' => 1, 'is_locked' => true]);
    $unprotected = (new Contact)->forceFill(['id' => 2, 'is_locked' => false]);

    $makeConditional = fn (Contact $record): AdvancedToggle => makeToggle(record: $record)
        ->requiresConfirmation(fn (Contact $record): bool => $record->is_locked);

    expect($makeConditional($protected)->isConfirmationRequiredForNewState(true))->toBeTrue()
        ->and($makeConditional($unprotected)->isConfirmationRequiredForNewState(true))->toBeFalse();
});

it('scopes confirmation to a single direction', function () {
    $onlyOn = makeToggle()->requiresConfirmation(onlyWhenTurningOn: true);
    $onlyOff = makeToggle()->requiresConfirmation(onlyWhenTurningOff: true);

    expect($onlyOn->isConfirmationRequiredForNewState(true))->toBeTrue()
        ->and($onlyOn->isConfirmationRequiredForNewState(false))->toBeFalse()
        ->and($onlyOff->isConfirmationRequiredForNewState(false))->toBeTrue()
        ->and($onlyOff->isConfirmationRequiredForNewState(true))->toBeFalse();
});

it('configures modal appearance via requiresConfirmation() named arguments', function () {
    $toggle = makeToggle()->requiresConfirmation(
        title: 'Enable feature',
        description: 'Are you sure?',
        icon: 'heroicon-o-bolt',
        iconColor: 'warning',
        confirmButtonLabel: 'Yes, enable it',
        cancelButtonLabel: 'Never mind',
        confirmButtonColor: 'success',
        cancelButtonColor: 'gray',
    );

    expect($toggle->getConfirmationHeading())->toBe('Enable feature')
        ->and($toggle->getConfirmationDescription())->toBe('Are you sure?')
        ->and($toggle->getConfirmationIcon())->toBe('heroicon-o-bolt')
        ->and($toggle->getConfirmationIconColor())->toBe('warning')
        ->and($toggle->getConfirmationConfirmButtonLabel())->toBe('Yes, enable it')
        ->and($toggle->getConfirmationCancelButtonLabel())->toBe('Never mind')
        ->and($toggle->getConfirmationConfirmButtonColor())->toBe('success')
        ->and($toggle->getConfirmationCancelButtonColor())->toBe('gray');
});

it('lets heading win over title when both are given, and title stand alone otherwise', function () {
    $titleOnly = makeToggle()->requiresConfirmation(title: 'From title');
    $both = makeToggle()->requiresConfirmation(title: 'From title', heading: 'From heading');

    expect($titleOnly->getConfirmationHeading())->toBe('From title')
        ->and($both->getConfirmationHeading())->toBe('From heading');
});

it('also exposes every appearance option as a dedicated fluent setter', function () {
    $toggle = makeToggle()
        ->requiresConfirmation()
        ->confirmationTitle('Title')
        ->confirmationDescription('Description')
        ->confirmationIcon('heroicon-o-bolt')
        ->confirmationIconColor('danger')
        ->confirmationWidth('sm')
        ->confirmationAlignment('start')
        ->confirmationConfirmButtonLabel('Go')
        ->confirmationCancelButtonLabel('Stop')
        ->confirmationConfirmButtonColor('success')
        ->confirmationCancelButtonColor('gray');

    expect($toggle->getConfirmationTitle())->toBe('Title')
        ->and($toggle->getConfirmationDescription())->toBe('Description')
        ->and($toggle->getConfirmationIcon())->toBe('heroicon-o-bolt')
        ->and($toggle->getConfirmationIconColor())->toBe('danger')
        ->and($toggle->getConfirmationWidth())->toBe('sm')
        ->and($toggle->getConfirmationAlignment())->toBe('start')
        ->and($toggle->getConfirmationConfirmButtonLabel())->toBe('Go')
        ->and($toggle->getConfirmationCancelButtonLabel())->toBe('Stop');
});

// ---------------------------------------------------------------------------
// Confirmation action construction (ConfirmationManager)
// ---------------------------------------------------------------------------

it('builds a native Filament action named "confirm" that always requires confirmation', function () {
    $toggle = makeToggle()->requiresConfirmation(title: 'Enable feature');

    // getAction() (not getConfirmationAction() directly) goes through the
    // schema-level cacheActions()/prepareAction() path, which is what binds
    // schemaComponent() — exactly how a real mount resolves it.
    $action = $toggle->getAction('confirm');

    expect($action->getName())->toBe('confirm')
        ->and($action->isConfirmationRequired())->toBeTrue()
        ->and($action->getModalHeading())->toBe('Enable feature')
        ->and($action->getSchemaComponent())->toBe($toggle);
});

it('registers the confirmation action only when confirmation is configured', function () {
    expect(makeToggle()->getDefaultActions())->toBe([]);

    $toggle = makeToggle()->requiresConfirmation();

    expect($toggle->getDefaultActions())->toHaveCount(1)
        ->and($toggle->getActions())->toHaveKey('confirm');
});

it('forwards confirmationSchema() to the action schema untouched', function () {
    $toggle = makeToggle()->requiresConfirmation()->confirmationSchema([
        TextInput::make('password')->required(),
    ]);

    $schema = $toggle->getConfirmationAction()->getSchema(Schema::make(new SchemaLivewireComponent));

    expect($schema)->not->toBeNull()
        ->and($schema->getComponents())->toHaveCount(1);
});

it('lets the confirmation action builder be swapped per-instance', function () {
    $customAction = Action::make('confirm');

    $builder = new class($customAction) implements BuildsConfirmationAction
    {
        public function __construct(private Action $action) {}

        public function build(AdvancedToggle $toggle): Action
        {
            return $this->action;
        }
    };

    $toggle = makeToggle()->requiresConfirmation()->buildConfirmationActionUsing($builder);

    expect($toggle->getConfirmationAction())->toBe($customAction);
});

// ---------------------------------------------------------------------------
// The confirmation guarantee: state never mutates outside a confirmed call
// ---------------------------------------------------------------------------

it('does not mutate state when the confirmation action is only mounted', function () {
    $toggle = makeToggle()->requiresConfirmation();

    mountConfirmable($toggle, initialState: false)
        ->call('mountAction', 'confirm', ['state' => true], ['schemaComponent' => 'form.enabled'])
        ->assertSet('data.enabled', false);
});

it('commits the new state only once the confirmation action is called', function () {
    $toggle = makeToggle()->requiresConfirmation();

    mountConfirmable($toggle, initialState: false)
        ->call('mountAction', 'confirm', ['state' => true], ['schemaComponent' => 'form.enabled'])
        ->call('callMountedAction')
        ->assertSet('data.enabled', true);
});

it('supports the same flow turning the toggle off', function () {
    $toggle = makeToggle()->requiresConfirmation();

    mountConfirmable($toggle, initialState: true)
        ->call('mountAction', 'confirm', ['state' => false], ['schemaComponent' => 'form.enabled'])
        ->assertSet('data.enabled', true)
        ->call('callMountedAction')
        ->assertSet('data.enabled', false);
});

it('passes newState, oldState, and the confirmation form data to onConfirm()', function () {
    $received = null;

    $toggle = makeToggle()
        ->requiresConfirmation()
        ->confirmationSchema([TextInput::make('reason')])
        ->onConfirm(function (bool $newState, bool $oldState, array $data) use (&$received): void {
            $received = [$newState, $oldState, $data];
        });

    mountConfirmable($toggle, initialState: false)
        ->call('mountAction', 'confirm', ['state' => true], ['schemaComponent' => 'form.enabled'])
        ->set('mountedActions.0.data.reason', 'Because I said so')
        ->call('callMountedAction');

    expect($received[0])->toBeTrue()
        ->and($received[1])->toBeFalse()
        ->and($received[2])->toBe(['reason' => 'Because I said so']);
});

it('fires afterConfirmed() only after the state has been committed', function () {
    $stateWhenFired = null;

    $toggle = makeToggle()
        ->requiresConfirmation()
        ->afterConfirmed(function (bool $newState) use (&$stateWhenFired): void {
            $stateWhenFired = $newState;
        });

    mountConfirmable($toggle, initialState: false)
        ->call('mountAction', 'confirm', ['state' => true], ['schemaComponent' => 'form.enabled'])
        ->call('callMountedAction')
        ->assertSet('data.enabled', true);

    expect($stateWhenFired)->toBeTrue();
});

it('keeps the previous state and the modal open when onConfirm() throws, by default', function () {
    $toggle = makeToggle()
        ->requiresConfirmation()
        ->onConfirm(function (): void {
            throw new RuntimeException('Something went wrong.');
        });

    mountConfirmable($toggle, initialState: false)
        ->call('mountAction', 'confirm', ['state' => true], ['schemaComponent' => 'form.enabled'])
        ->call('callMountedAction')
        ->assertSet('data.enabled', false)
        ->assertSet('mountedActions.0.name', 'confirm');
});

it('closes the modal on failure when configured to, while still keeping the previous state', function () {
    $toggle = makeToggle()
        ->requiresConfirmation()
        ->closeConfirmationModalOnFailure()
        ->onConfirm(function (): void {
            throw new RuntimeException('Something went wrong.');
        });

    mountConfirmable($toggle, initialState: false)
        ->call('mountAction', 'confirm', ['state' => true], ['schemaComponent' => 'form.enabled'])
        ->call('callMountedAction')
        ->assertSet('data.enabled', false)
        ->assertNotSet('mountedActions.0.name', 'confirm');
});

it('never runs onConfirm() or mutates state when confirmation schema validation fails', function () {
    $ranConfirm = false;

    $toggle = makeToggle()
        ->requiresConfirmation()
        ->confirmationSchema([TextInput::make('password')->required()])
        ->onConfirm(function () use (&$ranConfirm): void {
            $ranConfirm = true;
        });

    mountConfirmable($toggle, initialState: false)
        ->call('mountAction', 'confirm', ['state' => true], ['schemaComponent' => 'form.enabled'])
        ->call('callMountedAction')
        ->assertSet('data.enabled', false)
        ->assertSet('mountedActions.0.name', 'confirm');

    expect($ranConfirm)->toBeFalse();
});

it('fires onCancel() without ever touching the state', function () {
    $received = null;

    $toggle = makeToggle()
        ->requiresConfirmation()
        ->onCancel(function (bool $oldState) use (&$received): void {
            $received = $oldState;
        });

    // The cancel button is a *nested* modal action of 'confirm' — its exact
    // client-side mount/resolve wiring is Filament's own internal plumbing,
    // not this package's logic. Calling it directly exercises exactly what
    // this package is responsible for: the closure built in
    // ConfirmationManager::buildCancelActionModifier() invokes onCancel()
    // with the current (untouched) state, and never calls $toggle->state().
    $toggle->getAction('confirm')->getModalCancelAction()->call();

    expect($received)->toBeFalse()
        ->and((bool) $toggle->getState())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Dynamic labels, descriptions, tooltips, badges, disabled reason
// ---------------------------------------------------------------------------

it('resolves labels, descriptions, tooltips, and badges per state', function () {
    $toggle = makeToggle()
        ->onLabel('Enabled')->offLabel('Disabled')
        ->onDescription('Users can access this feature.')->offDescription('Feature is disabled.')
        ->onTooltip('Click to disable')->offTooltip('Click to enable')
        ->onBadge('Enabled', 'success')->offBadge('Disabled', 'gray');

    expect($toggle->getStateLabel(true))->toBe('Enabled')
        ->and($toggle->getStateLabel(false))->toBe('Disabled')
        ->and($toggle->getStateDescription(true))->toBe('Users can access this feature.')
        ->and($toggle->getStateDescription(false))->toBe('Feature is disabled.')
        ->and($toggle->getStateTooltip(true))->toBe('Click to disable')
        ->and($toggle->getStateTooltip(false))->toBe('Click to enable')
        ->and($toggle->getStateBadge(true))->toBe('Enabled')
        ->and($toggle->getStateBadgeColor(true))->toBe('success')
        ->and($toggle->getStateBadge(false))->toBe('Disabled')
        ->and($toggle->getStateBadgeColor(false))->toBe('gray');
});

it('only exposes a disabled reason while genuinely disabled', function () {
    $enabled = makeToggle()->disabledReason('Locked');
    $disabled = makeToggle()->disabled()->disabledReason('Locked because this record is protected.');

    expect($enabled->getDisabledReason())->toBeNull()
        ->and($disabled->getDisabledReason())->toBe('Locked because this record is protected.');
});

it('configures the animation duration and can disable animation entirely', function () {
    $default = makeToggle();
    $custom = makeToggle()->animationDuration(250);
    $disabled = makeToggle()->animated(false);

    expect($default->isAnimated())->toBeTrue()
        ->and($custom->getAnimationDuration())->toBe(250)
        ->and($disabled->isAnimated())->toBeFalse()
        ->and($disabled->getViewModel()->animationDurationCss())->toBe('0s');
});

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

it('renders the click-interception wrapper only when confirmation can intercept the click', function () {
    $html = renderToggle(makeToggle()->requiresConfirmation());

    expect($html)->toContain('fi-advanced-toggle-ctn')
        ->and($html)->toContain('mountAction')
        ->and($html)->toContain('confirm');
});

it('skips the wrapper when confirmation is configured but resolves to false for both directions', function () {
    $html = renderToggle(makeToggle()->requiresConfirmation(fn (): bool => false));

    expect($html)->not->toContain('fi-advanced-toggle-ctn');
});

it('renders the badge, description, and disabled reason under the toggle', function () {
    $html = renderToggle(
        makeToggle()
            ->disabled()
            ->offBadge('Disabled', 'gray')
            ->offDescription('Feature is disabled.')
            ->disabledReason('Locked because this record is protected.')
    );

    expect($html)->toContain('fi-advanced-toggle-meta')
        ->and($html)->toContain('Disabled')
        ->and($html)->toContain('Feature is disabled.')
        ->and($html)->toContain('Locked because this record is protected.');
});

it('uses ConfirmationManager as the default confirmation action builder', function () {
    expect(makeToggle()->getConfirmationActionBuilder())->toBeInstanceOf(ConfirmationManager::class);
});
