<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\View\Components\Columns\TextColumnComponent\ItemComponent\IconComponent;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns\HasAdvancedText;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\GeneratesLinks;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\MasksText;
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\AdvancedTextEntry;
use Throwable;

/**
 * A drop-in replacement for {@see TextColumn} with advanced ergonomics:
 * masking, contact links, affix images and icons, extra typography, and a
 * character count — every native `TextColumn` feature keeps working.
 *
 * ```php
 * AdvancedTextColumn::make('email')
 *     ->searchable()
 *     ->sortable()
 *     ->copyable()
 *     ->mailable()
 *     ->bold(fn (User $record): bool => $record->is_admin)
 *     ->prefixImage(fn (User $record): string => $record->avatar_url)
 *     ->imageCircular();
 * ```
 *
 * Rendering strategy: the native cell is rendered by the parent unchanged,
 * then wrapped — never rewritten — so the column stays compatible with
 * upstream markup changes. Masking hooks into `formatState()`, which also
 * feeds the copyable fallback, so a masked value is not leaked through the
 * clipboard. Contact links hook into `getUrl()` and never override an
 * explicit `url()`.
 *
 * The full configuration API lives in {@see HasAdvancedText}, shared with
 * the infolist counterpart, {@see AdvancedTextEntry}.
 *
 * Extension points:
 * - subclass and override `getImageRenderer()` or `wrapEmbeddedHtml()`,
 * - `decorateHtmlUsing()` for ad-hoc rendering decorators,
 * - `AdvancedTextColumn::macro()` for new fluent methods,
 * - `AdvancedTextColumn::configureUsing()` for global defaults,
 * - rebind the {@see MasksText} or {@see GeneratesLinks} contracts to swap
 *   the masking and link-generation services.
 */
class AdvancedTextColumn extends TextColumn
{
    use HasAdvancedText;

    public function toEmbeddedHtml(): string
    {
        return $this->decorateAdvancedTextHtml(parent::toEmbeddedHtml());
    }

    protected function getAffixIconHtmlComponent(): string
    {
        return IconComponent::class;
    }

    /**
     * Filament wraps a table cell in an `<a>` or `<button>` when the column
     * has a (non-state-based) `url()`/`action()`, or the table has a
     * `recordUrl`/`recordAction`. Interactive badges cannot be nested in
     * such a wrapper, so they degrade to scripted, accessible elements when
     * this returns `true`.
     *
     * State-based (per-cell) URLs — including the `mailable()` /
     * `callable()` / `whatsappable()` links — wrap only the content, not the
     * whole cell, so they never trigger this.
     */
    protected function areAdvancedBadgesNestedInInteractiveElement(): bool
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
