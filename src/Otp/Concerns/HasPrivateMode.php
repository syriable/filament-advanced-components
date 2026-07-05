<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns;

use Closure;

/**
 * "Private" (masked) mode: each filled cell shows a mask glyph instead of the
 * real character, like a password field, while the true value is still stored
 * and submitted. Useful for recovery codes or PINs entered in public.
 */
trait HasPrivateMode
{
    protected bool | Closure $isPrivate = false;

    protected string | Closure $maskCharacter = '•';

    public function private(bool | Closure $condition = true): static
    {
        $this->isPrivate = $condition;

        return $this;
    }

    /**
     * The glyph shown in place of a real character while
     * {@see private() private} mode is on.
     */
    public function maskCharacter(string | Closure $character): static
    {
        $this->maskCharacter = $character;

        return $this;
    }

    public function isPrivate(): bool
    {
        return (bool) $this->evaluate($this->isPrivate);
    }

    public function getMaskCharacter(): string
    {
        $character = (string) $this->evaluate($this->maskCharacter);

        // A single visible glyph, defaulting to a bullet — never empty, or a
        // masked cell would look unfilled.
        return mb_substr($character, 0, 1) ?: '•';
    }
}
