<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\MultiProgressEntry;
use Syriable\Filament\Plugins\AdvancedComponents\MultiProgress\Concerns\HasMultiProgressBar;
use Throwable;

/**
 * A table column that renders a single progress bar divided into multiple
 * colored segments — like the language progress bars on translation
 * dashboards such as Crowdin or Lokalise.
 *
 * ```php
 * MultiProgressColumn::make('translation_progress')
 *     ->segments(fn (Language $record): array => [
 *         ['label' => 'Translated', 'value' => $record->translated_count, 'color' => 'success'],
 *         ['label' => 'Needs Review', 'value' => $record->review_count, 'color' => 'warning'],
 *         ['label' => 'Missing', 'value' => $record->missing_count, 'color' => 'danger'],
 *     ])
 *     ->total(fn (Language $record): int => $record->keys_count)
 *     ->valueSuffix('keys')
 *     ->showPercentage()
 *     ->showPercentageFrom('md')
 *     ->showTotal()
 *     ->showTotalFrom('md')
 *     ->showLegend()
 *     ->showLegendFrom('md');
 * ```
 *
 * The full configuration API lives in {@see HasMultiProgressBar}, shared
 * with the infolist counterpart,
 * {@see MultiProgressEntry}.
 */
class MultiProgressColumn extends Column
{
    use HasMultiProgressBar;

    protected string $view = 'filament-advanced-components::components.multi-progress';

    /**
     * Alias of {@see HasMultiProgressBar::segmentGap()}. Columns can afford
     * the short name; infolist entries cannot, because they inherit
     * Filament's schema-level `gap()` toggle.
     */
    public function gap(int | Closure $pixels): static
    {
        return $this->segmentGap($pixels);
    }

    /**
     * Filament wraps a table cell in an `<a>`/`<button>` when the column has
     * a (non-state-based) `url()`/`action()`, or the table has a
     * `recordUrl`/`recordAction`. A clickable segment cannot be a real `<a>`
     * nested inside that wrapper — the browser would tear the markup apart —
     * so segments degrade to scripted, accessible `role="link"` elements
     * when this returns `true`.
     */
    public function isNestedInInteractiveElement(): bool
    {
        if ($this->isClickDisabled() || $this->hasStateBasedUrls()) {
            return false;
        }

        if (filled($this->getUrl()) || filled($this->getAction())) {
            return true;
        }

        try {
            $table = $this->getTable();
            $record = $this->getRecord();
        } catch (Throwable) {
            return false;
        }

        if ($record === null) {
            return false;
        }

        return filled($table->getRecordUrl($record)) || filled($table->getRecordAction($record));
    }
}
