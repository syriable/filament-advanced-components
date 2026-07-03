<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Syriable\Filament\Plugins\AdvancedComponents\MultiProgress\Concerns\HasMultiProgressBar;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\MultiProgressColumn;

/**
 * An infolist entry that renders a single progress bar divided into multiple
 * colored segments — the infolist counterpart of
 * {@see MultiProgressColumn}.
 *
 * ```php
 * MultiProgressEntry::make('translation_progress')
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
 * The full configuration API lives in {@see HasMultiProgressBar} and is
 * identical to the table column's, so a bar can be moved between a table
 * and an infolist by swapping the class name.
 */
class MultiProgressEntry extends Entry
{
    use HasMultiProgressBar;

    protected string $view = 'filament-advanced-components::components.multi-progress';
}
