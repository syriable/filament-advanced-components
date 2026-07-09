<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffFile;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Support\DiffGenerator;

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
 */
final class DiffField extends Field
{
    protected string $view = 'filament-advanced-components::components.diff-field';

    protected string | Closure $oldValue = '';

    protected string | Closure $newValue = '';

    protected int | Closure $contextLines = 3;

    protected string | Closure | null $filename = null;

    protected ?DiffFile $cachedDiffFile = null;

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

        return $this;
    }

    /**
     * The revised ("after") text.
     */
    public function newValue(string | Closure $value): static
    {
        $this->newValue = $value;
        $this->cachedDiffFile = null;

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
}
