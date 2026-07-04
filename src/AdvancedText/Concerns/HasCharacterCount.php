<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns;

use Closure;

/**
 * Renders a small character count — optionally against a limit — next to
 * the cell's content.
 *
 * ```php
 * AdvancedTextColumn::make('bio')
 *     ->characterCount();               // “142”
 *
 * AdvancedTextColumn::make('bio')
 *     ->characterLimitIndicator(160);   // “142 / 160”, turns red when over
 * ```
 */
trait HasCharacterCount
{
    protected bool | Closure $hasCharacterCount = false;

    protected int | Closure | null $characterCountLimit = null;

    /**
     * Show the number of characters in the (unmasked, unformatted) state.
     */
    public function characterCount(bool | Closure $condition = true): static
    {
        $this->hasCharacterCount = $condition;

        return $this;
    }

    /**
     * Show the character count against a limit, e.g. `142 / 160`. The
     * indicator is highlighted when the state exceeds the limit.
     */
    public function characterLimitIndicator(int | Closure $limit): static
    {
        $this->hasCharacterCount = true;
        $this->characterCountLimit = $limit;

        return $this;
    }

    public function hasCharacterCount(): bool
    {
        return (bool) $this->evaluate($this->hasCharacterCount);
    }

    public function getCharacterCountLimit(): ?int
    {
        $limit = $this->evaluate($this->characterCountLimit);

        return $limit === null ? null : (int) $limit;
    }

    /**
     * Character count of the raw state — the mask and `limit()` truncation
     * are presentation concerns, so they don't change the reported length.
     */
    public function getCharacterCount(mixed $state): int
    {
        if (is_array($state)) {
            $state = implode('', array_map(strval(...), array_filter($state, is_scalar(...))));
        }

        if (! is_scalar($state)) {
            return 0;
        }

        return mb_strlen((string) $state);
    }

    public function isCharacterCountExceeded(mixed $state): bool
    {
        $limit = $this->getCharacterCountLimit();

        return $limit !== null && $this->getCharacterCount($state) > $limit;
    }
}
