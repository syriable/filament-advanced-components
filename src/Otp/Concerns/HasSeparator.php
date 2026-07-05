<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns;

use Closure;

/**
 * The glyph drawn between {@see HasGrouping groups} — `-`, `•`, `|`, or any
 * string. Purely decorative and never part of the stored value; it is marked
 * `aria-hidden` so screen readers announce a clean code.
 */
trait HasSeparator
{
    protected string | Closure | null $separator = null;

    public function separator(string | Closure | null $separator = '-'): static
    {
        $this->separator = $separator;

        return $this;
    }

    public function getSeparator(): ?string
    {
        $separator = $this->evaluate($this->separator);

        return filled($separator) ? (string) $separator : null;
    }

    /**
     * Whether `separator()` has been called at all — regardless of the value
     * it was given (including an explicit `null` to opt out). Used to
     * auto-pair grouping and separators without ever overwriting an explicit
     * choice.
     */
    public function isSeparatorConfigured(): bool
    {
        return $this->separator !== null;
    }
}
