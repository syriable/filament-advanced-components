<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\MasksText;

/**
 * Masks the column's state before it is rendered.
 *
 * ```php
 * AdvancedTextColumn::make('phone')
 *     ->masked()
 *     ->maskIndex(3)
 *     ->maskLength(5)
 *     ->maskCharacter('*');
 *
 * AdvancedTextColumn::make('email')
 *     ->maskEmail(fn () => auth()->user()->cannot('viewSensitiveData'));
 * ```
 *
 * Masking is applied after Filament's own formatting (`formatStateUsing()`,
 * `limit()`, dates, money, …) so the mask always operates on what would
 * otherwise reach the browser. The copyable state and tooltips fall back to
 * the masked value too, so an enabled mask never leaks through a side
 * channel.
 */
trait HasTextMask
{
    protected bool | Closure $isMasked = false;

    protected bool | Closure $isEmailMasked = false;

    protected string | Closure $maskCharacter = '•';

    protected int | Closure $maskIndex = 0;

    protected int | Closure | null $maskLength = null;

    protected ?Closure $maskStateUsing = null;

    /**
     * Mask the state, replacing every character from `maskIndex()` for
     * `maskLength()` characters with `maskCharacter()`. By default the whole
     * value is masked.
     */
    public function masked(bool | Closure $condition = true): static
    {
        $this->isMasked = $condition;

        return $this;
    }

    /**
     * Mask the local part of an email address while keeping the first
     * character and the domain visible: `jane@example.com` → `j•••@example.com`.
     */
    public function maskEmail(bool | Closure $condition = true): static
    {
        $this->isEmailMasked = $condition;

        return $this;
    }

    /**
     * The character rendered in place of each masked character.
     */
    public function maskCharacter(string | Closure $character): static
    {
        $this->maskCharacter = $character;

        return $this;
    }

    /**
     * Zero-based offset of the first masked character. Negative values count
     * from the end of the string.
     */
    public function maskIndex(int | Closure $index): static
    {
        $this->maskIndex = $index;

        return $this;
    }

    /**
     * How many characters to mask. When `null`, everything from `maskIndex()`
     * to the end of the string is masked.
     */
    public function maskLength(int | Closure | null $length): static
    {
        $this->maskLength = $length;

        return $this;
    }

    /**
     * Take full control of masking. The closure receives the formatted
     * `$state` and should return the masked string. Enables masking on its
     * own — no separate `masked()` call is required.
     *
     * ```php
     * ->maskStateUsing(fn (string $state): string => Str::mask($state, '#', 2))
     * ```
     */
    public function maskStateUsing(?Closure $callback): static
    {
        $this->maskStateUsing = $callback;

        return $this;
    }

    public function isMasked(): bool
    {
        if ($this->evaluate($this->isMasked)) {
            return true;
        }
        if ($this->evaluate($this->isEmailMasked)) {
            return true;
        }

        return $this->maskStateUsing !== null;
    }

    public function getMaskCharacter(): string
    {
        return (string) $this->evaluate($this->maskCharacter);
    }

    public function getMaskIndex(): int
    {
        return (int) $this->evaluate($this->maskIndex);
    }

    public function getMaskLength(): ?int
    {
        $length = $this->evaluate($this->maskLength);

        return $length === null ? null : (int) $length;
    }

    /**
     * Apply the configured mask to an already formatted state.
     */
    public function applyTextMask(string $state): string
    {
        if ($state === '') {
            return $state;
        }

        if ($this->maskStateUsing !== null) {
            return (string) $this->evaluate($this->maskStateUsing, [
                'state' => $state,
            ]);
        }

        $masker = app(MasksText::class);

        if ($this->evaluate($this->isEmailMasked)) {
            return $masker->maskEmail($state, $this->getMaskCharacter());
        }

        return $masker->mask(
            $state,
            $this->getMaskCharacter(),
            $this->getMaskIndex(),
            $this->getMaskLength(),
        );
    }
}
