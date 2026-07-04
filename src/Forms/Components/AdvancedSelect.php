<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Forms\Components;

use Filament\Forms\Components\Select;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Concerns\HasOptionDecorators;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Concerns\HasRichOptions;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Concerns\HasSelectPresets;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Contracts\RendersOptions;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\OptionViewModel;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\SelectOption;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options\SelectOptionCollection;

/**
 * A drop-in superset of Filament's {@see Select} that renders rich options —
 * a leading icon, a description line, a Filament color, and a trailing badge —
 * per option, with lazy evaluation on every value.
 *
 * ```php
 * AdvancedSelect::make('status')
 *     ->placeholder('Select a status')
 *     ->searchable()
 *     ->options([
 *         SelectOption::make('draft', 'Draft')
 *             ->icon('heroicon-o-pencil-square')
 *             ->description('Only visible to you')
 *             ->color('gray'),
 *         SelectOption::make('published', 'Published')
 *             ->icon('heroicon-o-globe-alt')
 *             ->description('Visible to everyone')
 *             ->color('success')
 *             ->badge('Live'),
 *         SelectOption::make('archived', 'Archived')
 *             ->icon('heroicon-o-archive-box')
 *             ->color('warning')
 *             ->disabled(fn (?Post $record): bool => $record?->is_locked ?? false),
 *     ]);
 * ```
 *
 * The same information can be layered onto a plain `value => label` map through
 * the parallel {@see HasRichOptions::descriptions()}, `icons()`,
 * `optionColors()`, and `optionBadges()` helpers:
 *
 * ```php
 * AdvancedSelect::make('status')
 *     ->options(['draft' => 'Draft', 'published' => 'Published'])
 *     ->icons(['draft' => 'heroicon-o-pencil-square', 'published' => 'heroicon-o-globe-alt'])
 *     ->descriptions(['draft' => 'Only visible to you'])
 *     ->optionColors(['published' => 'success']);
 * ```
 *
 * ## Architecture
 *
 * Because it extends `Select`, it inherits everything the native field does —
 * relationships, `multiple()`, validation, create/edit option actions,
 * dehydration, Livewire reactivity — and layers the rich rendering on top by
 * feeding the native extension points (`options()`, `getOptionLabelUsing()`,
 * `getSearchResultsUsing()`, `disableOptionWhen()`, `allowHtml()`). Nothing is
 * duplicated; a plain `AdvancedSelect` behaves exactly like a `Select`.
 *
 * Responsibilities are split across small, single-purpose collaborators:
 *
 *  - {@see SelectOption} — fluent, lazily evaluated option definition;
 *  - {@see SelectOptionCollection} — normalization;
 *  - {@see OptionViewModel} — the resolved, render-ready state;
 *  - {@see RendersOptions} — the swappable renderer;
 *  - {@see HasRichOptions}, {@see HasOptionDecorators}, {@see HasSelectPresets} — the feature traits.
 */
class AdvancedSelect extends Select
{
    use HasOptionDecorators;
    use HasRichOptions;
    use HasSelectPresets;
}
