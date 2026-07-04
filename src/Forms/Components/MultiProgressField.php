<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Forms\Components;

use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\MultiProgressEntry;
use Syriable\Filament\Plugins\AdvancedComponents\MultiProgress\Concerns\HasMultiProgressBar;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\MultiProgressColumn;

/**
 * A read-only form field that renders a single progress bar divided into
 * multiple colored segments — the forms counterpart of
 * {@see MultiProgressColumn} and {@see MultiProgressEntry}.
 *
 * ```php
 * MultiProgressField::make('translation_progress')
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
 * Being a real {@see Field}, it participates in the form's state: without a
 * `segments()` closure it reads its own state path, so a model attribute (or
 * JSON cast) holding a segments array hydrates it with zero configuration,
 * and a closure receives `$get` to recompute the bar live as other fields
 * change. It is display-only — `dehydrated(false)` by default — so it never
 * writes anything back on submit.
 *
 * The full configuration API lives in {@see HasMultiProgressBar} and is
 * identical to the table column's and infolist entry's.
 */
class MultiProgressField extends Field
{
    use HasMultiProgressBar;
    use HasPlaceholder;

    protected string $view = 'filament-advanced-components::components.multi-progress-field';

    protected function setUp(): void
    {
        parent::setUp();

        // The bar is a display, not an input: never dehydrate its state back
        // into the form payload on submit.
        $this->dehydrated(false);
    }
}
