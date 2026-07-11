<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Confirmation\ConfirmationManager;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Contracts\BuildsRollbackAction;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffFile;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\WordDiffToken;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Rollback\RollbackManager;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Support\DiffGenerator;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Support\WordDiffGenerator;

/**
 * A GitHub-style unified diff view as a first-class Filament form field:
 * dual line-number gutters, red/green row backgrounds, and long runs of
 * unchanged context collapsed behind an "Expand N hidden lines" toggle
 * (handled client-side by Alpine — the hidden rows are already in the DOM,
 * so expanding never round-trips to the server).
 *
 * ```php
 * DiffField::make('changes')
 *     ->filename('config/app.php')
 *     ->oldValue(fn (Revision $record): string => $record->previous_content)
 *     ->newValue(fn (Revision $record): string => $record->content)
 *     ->contextLines(3);
 * ```
 *
 * The field diffs the two raw strings itself (via sebastian/diff) — it does
 * not accept pre-computed diff data. It is display-only: it captures no user
 * input and is never dehydrated back into the form payload.
 *
 * When either {@see oldValue()} or {@see newValue()} is empty or not set,
 * there is nothing meaningful to diff from or to, so the field reports no
 * changes instead of rendering the other side as an entirely added or
 * deleted file — see {@see DiffFile::hasNoChanges()}.
 *
 * ## Modal presentation
 *
 * For short values (a translation string, a validation message) the full
 * line-diff table is overkill. `->modal()` switches the field to a compact
 * trigger that opens a native Filament action modal — built entirely from
 * Filament's own action-modal machinery, no bespoke overlay markup — offering
 * a Side-by-side (plain old/new text) and Inline (word-level diff, with
 * strikethrough/underline spans) view, toggled client-side:
 *
 * ```php
 * DiffField::make('message')
 *     ->modal()
 *     ->oldValue(fn (Translation $record): string => $record->getOriginal('value'))
 *     ->newValue(fn (Translation $record): string => $record->value)
 *     ->onRollback(function (Translation $record, string $oldValue) {
 *         $record->update(['value' => $oldValue]);
 *     });
 * ```
 *
 * `onRollback()` is a closure hook, not a built-in write: the field stays
 * display-only and never touches persistence itself, consistent with the
 * rest of this package — the Rollback button only appears when a callback is
 * registered, and it is that callback's job to actually commit the change.
 */
final class DiffField extends Field
{
    protected string $view = 'filament-advanced-components::components.diff-field';

    protected string | Closure $oldValue = '';

    protected string | Closure $newValue = '';

    protected int | Closure $contextLines = 3;

    protected string | Closure | null $filename = null;

    protected bool $modal = false;

    protected ?Closure $onRollback = null;

    protected ?DiffFile $cachedDiffFile = null;

    /**
     * @var array<int, WordDiffToken>|null
     */
    protected ?array $cachedWordDiffTokens = null;

    protected ?Action $viewDiffAction = null;

    protected ?BuildsRollbackAction $rollbackActionBuilder = null;

    protected function setUp(): void
    {
        parent::setUp();

        // The diff is a display, not an input: never dehydrate its state
        // back into the form payload on submit.
        $this->dehydrated(false);
    }

    /**
     * The original ("before") text.
     */
    public function oldValue(string | Closure $value): static
    {
        $this->oldValue = $value;
        $this->cachedDiffFile = null;
        $this->cachedWordDiffTokens = null;

        return $this;
    }

    /**
     * The revised ("after") text.
     */
    public function newValue(string | Closure $value): static
    {
        $this->newValue = $value;
        $this->cachedDiffFile = null;
        $this->cachedWordDiffTokens = null;

        return $this;
    }

    /**
     * How many unchanged lines to keep visible around each change; longer
     * runs of context collapse into an expandable block. Defaults to 3.
     */
    public function contextLines(int | Closure $lines = 3): static
    {
        $this->contextLines = $lines;
        $this->cachedDiffFile = null;

        return $this;
    }

    /**
     * Render as a compact trigger that opens the diff in a modal (Side-by-
     * side and Inline word-diff views) instead of the full inline line-diff
     * table. Suited to short values — a translation string, a validation
     * message — where a whole-file table is overkill.
     */
    public function modal(bool $condition = true): static
    {
        $this->modal = $condition;

        return $this;
    }

    public function isModal(): bool
    {
        return $this->modal;
    }

    /**
     * Registers a "Rollback" button on the modal's footer that runs the
     * given callback (injected with `oldValue`, `newValue`, and the usual
     * `$record`/`$get`/etc.) when clicked. The field itself never writes
     * anything anywhere — actually committing the rollback is entirely the
     * callback's responsibility. Passing `null` removes the button.
     */
    public function onRollback(?Closure $callback): static
    {
        $this->onRollback = $callback;
        $this->viewDiffAction = null;

        return $this;
    }

    public function getOnRollbackCallback(): ?Closure
    {
        return $this->onRollback;
    }

    public function hasRollback(): bool
    {
        return $this->onRollback instanceof Closure;
    }

    /**
     * The filename shown in the diff header. Null falls back to the field's
     * label in the view.
     */
    public function filename(string | Closure | null $name = null): static
    {
        $this->filename = $name;
        $this->cachedDiffFile = null;

        return $this;
    }

    public function getOldValue(): string
    {
        return (string) $this->evaluate($this->oldValue);
    }

    public function getNewValue(): string
    {
        return (string) $this->evaluate($this->newValue);
    }

    public function getContextLines(): int
    {
        return max(0, (int) $this->evaluate($this->contextLines));
    }

    public function getFilename(): ?string
    {
        $filename = $this->evaluate($this->filename);

        return $filename === null ? null : (string) $filename;
    }

    /**
     * The header/trigger/modal title text: the configured filename, falling
     * back to the field's label, falling back to a generic placeholder.
     */
    public function getHeading(): string
    {
        $heading = $this->getFilename() ?? $this->getLabel();

        if (is_string($heading)) {
            return $heading;
        }

        if ($heading instanceof Htmlable) {
            return $heading->toHtml();
        }

        return self::translate('filament-advanced-components::diff-field.untitled');
    }

    /**
     * __() is typed to allow returning a translation array (for pluralized
     * groups); every key this component looks up is a plain string, so the
     * array branch never actually happens — this narrows it back to `string`
     * for callers (like {@see Action::label()}) that don't accept one.
     */
    private static function translate(string $key): string
    {
        $value = __($key);

        return is_string($value) ? $value : $key;
    }

    /**
     * The computed diff, memoized so repeated Blade render passes within the
     * same request never re-run the line comparison.
     */
    public function getDiffFile(): DiffFile
    {
        return $this->cachedDiffFile ??= DiffGenerator::diff(
            old: $this->getOldValue(),
            new: $this->getNewValue(),
            contextLines: $this->getContextLines(),
            filename: $this->getFilename(),
        );
    }

    /**
     * The word-level diff between {@see getOldValue()} and
     * {@see getNewValue()}, used by the modal's Inline view. Memoized like
     * {@see getDiffFile()}.
     *
     * @return array<int, WordDiffToken>
     */
    public function getWordDiffTokens(): array
    {
        return $this->cachedWordDiffTokens ??= WordDiffGenerator::diff($this->getOldValue(), $this->getNewValue());
    }

    public function getViewDiffActionName(): string
    {
        return 'viewDiff';
    }

    /**
     * Swap the Rollback button builder for this instance only, overriding
     * the container-bound {@see BuildsRollbackAction} default.
     */
    public function buildRollbackActionUsing(BuildsRollbackAction $builder): static
    {
        $this->rollbackActionBuilder = $builder;
        $this->viewDiffAction = null;

        return $this;
    }

    public function getRollbackActionBuilder(): BuildsRollbackAction
    {
        return $this->rollbackActionBuilder ??= app()->bound(BuildsRollbackAction::class)
            ? app(BuildsRollbackAction::class)
            : app(RollbackManager::class);
    }

    /**
     * The mounted action backing the modal — built entirely from Filament's
     * own action-modal machinery (heading, icon, submit/cancel buttons), so
     * no bespoke overlay markup exists anywhere in this component; only the
     * modal's body content ({@see resources/views/components/diff-field-modal.blade.php})
     * is custom. The Rollback submit button's appearance is delegated to
     * {@see getRollbackActionBuilder()} rather than configured inline, so it
     * can be swapped globally or per-instance without subclassing this field
     * — the same pattern {@see ConfirmationManager}
     * uses for `AdvancedToggle`'s confirmation modal.
     */
    public function getViewDiffAction(): Action
    {
        return $this->viewDiffAction ??= Action::make($this->getViewDiffActionName())
            ->label($this->getHeading())
            ->modalHeading($this->getHeading())
            ->modalIcon('heroicon-o-code-bracket')
            ->modalContent(fn (): View => view(
                'filament-advanced-components::components.diff-field-modal',
                ['field' => $this],
            ))
            ->modalSubmitAction($this->hasRollback()
                ? fn (Action $action): Action => $this->getRollbackActionBuilder()->build($this, $action)
                : false)
            ->modalCancelAction(false)
            ->action(function (DiffField $component): void {
                $component->handleRollback();
            });
    }

    /**
     * @return array<Action>
     */
    public function getDefaultActions(): array
    {
        if (! $this->isModal()) {
            return [];
        }

        return [$this->getViewDiffAction()];
    }

    /**
     * Runs the registered {@see onRollback()} callback, if any. Public only
     * so it can be invoked as the mounted action's handler via Filament's
     * own component-injection — the field never calls this itself.
     */
    public function handleRollback(): void
    {
        $callback = $this->getOnRollbackCallback();

        if (! $callback instanceof Closure) {
            return;
        }

        $this->evaluate($callback, [
            'oldValue' => $this->getOldValue(),
            'newValue' => $this->getNewValue(),
        ]);
    }
}
