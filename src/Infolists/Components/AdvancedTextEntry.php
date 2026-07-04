<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\View\Components\TextEntryComponent\ItemComponent\IconComponent;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns\HasAdvancedText;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\AdvancedTextColumn;

/**
 * The infolist counterpart of {@see AdvancedTextColumn}: a drop-in
 * replacement for {@see TextEntry} with an identical advanced configuration
 * API — swap the class name to move a component between a table and an
 * infolist:
 *
 * ```php
 * AdvancedTextEntry::make('email')
 *     ->copyable()
 *     ->mailable()
 *     ->maskEmail(fn (): bool => auth()->user()->cannot('viewSensitiveData'))
 *     ->prefixIcon(Heroicon::Envelope);
 * ```
 *
 * The full configuration API lives in {@see HasAdvancedText}, shared with
 * the table column.
 */
class AdvancedTextEntry extends TextEntry
{
    use HasAdvancedText;

    /**
     * The entry wrapper (label, hint, …) is applied by the parent after the
     * content is rendered, so the advanced decorations are injected here —
     * they hug the content, inside the wrapper.
     */
    public function wrapEmbeddedHtml(string $html): string
    {
        return parent::wrapEmbeddedHtml($this->decorateAdvancedTextHtml($html));
    }

    protected function getAffixIconHtmlComponent(): string
    {
        return IconComponent::class;
    }
}
