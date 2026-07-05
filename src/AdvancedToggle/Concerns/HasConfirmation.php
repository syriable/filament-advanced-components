<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Confirmation\ConfirmationManager;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\AdvancedToggle;
use Throwable;

/**
 * The core feature of {@see AdvancedToggle}:
 * gates a state change behind a confirmation modal, so the field's value
 * never mutates until the user actively confirms.
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->requiresConfirmation(
 *         title: 'Enable feature',
 *         description: 'Are you sure you want to enable this for everyone?',
 *     )
 *     ->onConfirm(function (bool $newState, bool $oldState, array $data) {
 *         // persist side effects, throw to reject
 *     })
 *     ->onCancel(fn (bool $oldState) => Log::info('Toggle cancelled'))
 *     ->afterConfirmed(fn (bool $newState) => AuditLog::record($newState));
 * ```
 *
 * Every appearance option doubles as a dedicated fluent setter
 * ({@see confirmationHeading()}, {@see confirmationDescription()}, ...) for
 * incremental configuration, and all of it accepts closures. Building the
 * actual {@see Action} from this configuration is the job
 * of {@see ConfirmationManager}
 * — this trait only stores *what* was configured.
 */
trait HasConfirmation
{
    protected bool $isConfirmationConfigured = false;

    protected bool | Closure $isConfirmationRequired = false;

    protected bool | Closure $isConfirmationOnlyWhenTurningOn = false;

    protected bool | Closure $isConfirmationOnlyWhenTurningOff = false;

    protected string | Htmlable | Closure | null $confirmationTitle = null;

    protected string | Htmlable | Closure | null $confirmationHeading = null;

    protected string | Htmlable | Closure | null $confirmationDescription = null;

    protected string | BackedEnum | Htmlable | Closure | null $confirmationIcon = null;

    /** @var string | array<int, string> | Closure | null */
    protected string | array | Closure | null $confirmationIconColor = null;

    protected Width | string | Closure | null $confirmationWidth = null;

    protected Alignment | string | Closure | null $confirmationAlignment = null;

    protected string | Closure | null $confirmationConfirmButtonLabel = null;

    protected string | Closure | null $confirmationCancelButtonLabel = null;

    /** @var string | array<int, string> | Closure | null */
    protected string | array | Closure | null $confirmationConfirmButtonColor = null;

    /** @var string | array<int, string> | Closure | null */
    protected string | array | Closure | null $confirmationCancelButtonColor = null;

    protected ?Closure $onConfirmCallback = null;

    protected ?Closure $onCancelCallback = null;

    protected ?Closure $afterConfirmedCallback = null;

    protected Notification | Closure | null $confirmationFailureNotification = null;

    protected bool $isConfirmationFailureNotificationDisabled = false;

    protected bool | Closure $shouldKeepConfirmationModalOpenOnFailure = true;

    /**
     * Requires confirmation before the toggle's state is committed. Accepts
     * a static value or a closure — including one receiving `$record`, for
     * per-record conditions:
     *
     * ```php
     * ->requiresConfirmation();
     * ->requiresConfirmation(fn ($record) => $record->isProtected());
     * ->requiresConfirmation(onlyWhenTurningOn: true);
     * ->requiresConfirmation(title: 'Enable feature', description: 'Are you sure?');
     * ```
     *
     * `$title` and `$heading` are the same concept — both are accepted since
     * either reads naturally; `$heading` wins if both are given.
     *
     * @param  string | array<int, string> | Closure | null  $iconColor
     * @param  string | array<int, string> | Closure | null  $confirmButtonColor
     * @param  string | array<int, string> | Closure | null  $cancelButtonColor
     */
    public function requiresConfirmation(
        bool | Closure $condition = true,
        string | Htmlable | Closure | null $title = null,
        string | Htmlable | Closure | null $heading = null,
        string | Htmlable | Closure | null $description = null,
        string | BackedEnum | Htmlable | Closure | null $icon = null,
        string | array | Closure | null $iconColor = null,
        Width | string | Closure | null $width = null,
        Alignment | string | Closure | null $alignment = null,
        string | Closure | null $confirmButtonLabel = null,
        string | Closure | null $cancelButtonLabel = null,
        string | array | Closure | null $confirmButtonColor = null,
        string | array | Closure | null $cancelButtonColor = null,
        bool | Closure $onlyWhenTurningOn = false,
        bool | Closure $onlyWhenTurningOff = false,
    ): static {
        $this->isConfirmationConfigured = true;
        $this->isConfirmationRequired = $condition;
        $this->isConfirmationOnlyWhenTurningOn = $onlyWhenTurningOn;
        $this->isConfirmationOnlyWhenTurningOff = $onlyWhenTurningOff;

        if ($title !== null) {
            $this->confirmationTitle = $title;
        }

        if ($heading !== null) {
            $this->confirmationHeading = $heading;
        }

        if ($description !== null) {
            $this->confirmationDescription = $description;
        }

        if ($icon !== null) {
            $this->confirmationIcon = $icon;
        }

        if ($iconColor !== null) {
            $this->confirmationIconColor = $iconColor;
        }

        if ($width !== null) {
            $this->confirmationWidth = $width;
        }

        if ($alignment !== null) {
            $this->confirmationAlignment = $alignment;
        }

        if ($confirmButtonLabel !== null) {
            $this->confirmationConfirmButtonLabel = $confirmButtonLabel;
        }

        if ($cancelButtonLabel !== null) {
            $this->confirmationCancelButtonLabel = $cancelButtonLabel;
        }

        if ($confirmButtonColor !== null) {
            $this->confirmationConfirmButtonColor = $confirmButtonColor;
        }

        if ($cancelButtonColor !== null) {
            $this->confirmationCancelButtonColor = $cancelButtonColor;
        }

        return $this;
    }

    /**
     * Only require confirmation when the requested state is `true`.
     */
    public function onlyWhenTurningOn(bool | Closure $condition = true): static
    {
        $this->isConfirmationOnlyWhenTurningOn = $condition;

        return $this;
    }

    /**
     * Only require confirmation when the requested state is `false`.
     */
    public function onlyWhenTurningOff(bool | Closure $condition = true): static
    {
        $this->isConfirmationOnlyWhenTurningOff = $condition;

        return $this;
    }

    public function confirmationTitle(string | Htmlable | Closure | null $title): static
    {
        $this->confirmationTitle = $title;

        return $this;
    }

    public function confirmationHeading(string | Htmlable | Closure | null $heading): static
    {
        $this->confirmationHeading = $heading;

        return $this;
    }

    public function confirmationDescription(string | Htmlable | Closure | null $description): static
    {
        $this->confirmationDescription = $description;

        return $this;
    }

    public function confirmationIcon(string | BackedEnum | Htmlable | Closure | null $icon): static
    {
        $this->confirmationIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<int, string> | Closure | null  $color
     */
    public function confirmationIconColor(string | array | Closure | null $color): static
    {
        $this->confirmationIconColor = $color;

        return $this;
    }

    public function confirmationWidth(Width | string | Closure | null $width): static
    {
        $this->confirmationWidth = $width;

        return $this;
    }

    public function confirmationAlignment(Alignment | string | Closure | null $alignment): static
    {
        $this->confirmationAlignment = $alignment;

        return $this;
    }

    public function confirmationConfirmButtonLabel(string | Closure | null $label): static
    {
        $this->confirmationConfirmButtonLabel = $label;

        return $this;
    }

    public function confirmationCancelButtonLabel(string | Closure | null $label): static
    {
        $this->confirmationCancelButtonLabel = $label;

        return $this;
    }

    /**
     * @param  string | array<int, string> | Closure | null  $color
     */
    public function confirmationConfirmButtonColor(string | array | Closure | null $color): static
    {
        $this->confirmationConfirmButtonColor = $color;

        return $this;
    }

    /**
     * @param  string | array<int, string> | Closure | null  $color
     */
    public function confirmationCancelButtonColor(string | array | Closure | null $color): static
    {
        $this->confirmationCancelButtonColor = $color;

        return $this;
    }

    /**
     * Called once confirmation succeeds — including any
     * {@see HasConfirmationForm::confirmationSchema()} data — and *before*
     * the new state is committed. Throwing keeps the previous state; see
     * {@see confirmationFailureNotification()} and
     * {@see keepConfirmationModalOpenOnFailure()} for what happens next.
     *
     * ```php
     * ->onConfirm(function (bool $newState, bool $oldState, array $data) {
     *     // ...
     * });
     * ```
     */
    public function onConfirm(?Closure $callback): static
    {
        $this->onConfirmCallback = $callback;

        return $this;
    }

    /**
     * Called when the user dismisses the confirmation modal without
     * confirming. The state is never touched either way; this is purely a
     * hook (e.g. for logging).
     */
    public function onCancel(?Closure $callback): static
    {
        $this->onCancelCallback = $callback;

        return $this;
    }

    /**
     * Called after the new state has been committed — a dedicated hook for
     * audit logging, distinct from {@see onConfirm()} which runs *before*
     * the commit and can still reject it.
     */
    public function afterConfirmed(?Closure $callback): static
    {
        $this->afterConfirmedCallback = $callback;

        return $this;
    }

    /**
     * Customizes (or disables, via `null`) the notification sent when
     * {@see onConfirm()} throws. Receives the `exception`, `oldState`, and
     * `newState`, plus a pre-built `notification` to modify and return.
     */
    public function confirmationFailureNotification(Notification | Closure | null $notification): static
    {
        $this->confirmationFailureNotification = $notification;
        $this->isConfirmationFailureNotificationDisabled = $notification === null;

        return $this;
    }

    /**
     * Whether the modal stays open (showing the failure notification, with
     * the previous state intact) or closes when {@see onConfirm()} throws.
     * Defaults to `true` — the safer default, since the user can see what
     * went wrong and retry without re-triggering the toggle.
     */
    public function keepConfirmationModalOpenOnFailure(bool | Closure $condition = true): static
    {
        $this->shouldKeepConfirmationModalOpenOnFailure = $condition;

        return $this;
    }

    /**
     * The inverse of {@see keepConfirmationModalOpenOnFailure()}.
     */
    public function closeConfirmationModalOnFailure(bool | Closure $condition = true): static
    {
        $this->shouldKeepConfirmationModalOpenOnFailure = fn (): bool => ! $this->evaluate($condition);

        return $this;
    }

    public function hasConfirmation(): bool
    {
        return $this->isConfirmationConfigured;
    }

    /**
     * Whether confirmation is required for a hypothetical transition *to*
     * `$newState` — combining the base condition with the on/off scoping.
     * For a boolean toggle, the "previous" state in that transition is
     * always the logical negation of `$newState`.
     */
    public function isConfirmationRequiredForNewState(bool $newState): bool
    {
        if (! $this->hasConfirmation()) {
            return false;
        }

        if ($newState && $this->evaluate($this->isConfirmationOnlyWhenTurningOff)) {
            return false;
        }

        if ((! $newState) && $this->evaluate($this->isConfirmationOnlyWhenTurningOn)) {
            return false;
        }

        return (bool) $this->evaluate($this->isConfirmationRequired, [
            'newState' => $newState,
            'oldState' => ! $newState,
            'state' => $newState,
        ]);
    }

    public function getConfirmationActionName(): string
    {
        return 'confirm';
    }

    public function getConfirmationTitle(): string | Htmlable | null
    {
        return $this->evaluate($this->confirmationTitle);
    }

    /**
     * The resolved modal heading — `heading` wins over `title` when both are
     * set, since they describe the same concept.
     */
    public function getConfirmationHeading(): string | Htmlable | null
    {
        return $this->evaluate($this->confirmationHeading) ?? $this->getConfirmationTitle();
    }

    public function getConfirmationDescription(): string | Htmlable | null
    {
        return $this->evaluate($this->confirmationDescription);
    }

    public function getConfirmationIcon(): string | BackedEnum | Htmlable | null
    {
        return $this->evaluate($this->confirmationIcon);
    }

    /**
     * @return string | array<int, string> | null
     */
    public function getConfirmationIconColor(): string | array | null
    {
        return $this->evaluate($this->confirmationIconColor);
    }

    public function getConfirmationWidth(): Width | string | null
    {
        return $this->evaluate($this->confirmationWidth);
    }

    public function getConfirmationAlignment(): Alignment | string | null
    {
        return $this->evaluate($this->confirmationAlignment);
    }

    public function getConfirmationConfirmButtonLabel(): ?string
    {
        return $this->evaluate($this->confirmationConfirmButtonLabel);
    }

    public function getConfirmationCancelButtonLabel(): ?string
    {
        return $this->evaluate($this->confirmationCancelButtonLabel);
    }

    /**
     * @return string | array<int, string> | null
     */
    public function getConfirmationConfirmButtonColor(): string | array | null
    {
        return $this->evaluate($this->confirmationConfirmButtonColor);
    }

    /**
     * @return string | array<int, string> | null
     */
    public function getConfirmationCancelButtonColor(): string | array | null
    {
        return $this->evaluate($this->confirmationCancelButtonColor);
    }

    public function getOnConfirmCallback(): ?Closure
    {
        return $this->onConfirmCallback;
    }

    public function getOnCancelCallback(): ?Closure
    {
        return $this->onCancelCallback;
    }

    public function getAfterConfirmedCallback(): ?Closure
    {
        return $this->afterConfirmedCallback;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function getConfirmationFailureNotification(Throwable $exception, bool $oldState, bool $newState, array $data = []): ?Notification
    {
        if ($this->isConfirmationFailureNotificationDisabled) {
            return null;
        }

        $default = Notification::make()
            ->danger()
            ->title(__('filament-advanced-components::advanced-toggle.notifications.confirmation_failed.title'))
            ->body($exception->getMessage());

        return $this->evaluate($this->confirmationFailureNotification, [
            'exception' => $exception,
            'oldState' => $oldState,
            'newState' => $newState,
            'data' => $data,
            'notification' => $default,
        ]) ?? $default;
    }

    public function shouldKeepConfirmationModalOpenOnFailure(): bool
    {
        return (bool) $this->evaluate($this->shouldKeepConfirmationModalOpenOnFailure);
    }
}
