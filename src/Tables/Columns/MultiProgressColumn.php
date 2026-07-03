<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\MultiProgressEntry;
use Syriable\Filament\Plugins\AdvancedComponents\MultiProgress\Concerns\HasMultiProgressBar;

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
 *     ->showLegend();
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

    protected function setUp(): void
    {
        parent::setUp();

        // A progress bar has no meaningful text state, so opt out of the
        // default "state description" behavior and keep the cell read-only.
        $this->disabledClick();
    }

    /**
     * Alias of {@see HasMultiProgressBar::segmentGap()}. Columns can afford
     * the short name; infolist entries cannot, because they inherit
     * Filament's schema-level `gap()` toggle.
     */
    public function gap(int | Closure $pixels): static
    {
        return $this->segmentGap($pixels);
    }
}
