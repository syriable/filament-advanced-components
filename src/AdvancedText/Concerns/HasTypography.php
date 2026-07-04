<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns;

use Closure;

/**
 * Typography modifiers that go beyond Filament's native `weight()`,
 * `fontFamily()`, and `size()`.
 *
 * ```php
 * AdvancedTextColumn::make('name')
 *     ->bold(fn ($record) => $record->is_admin)
 *     ->italic()
 *     ->underline()
 *     ->uppercase();
 * ```
 *
 * Every modifier accepts a boolean or a closure, evaluated per cell with
 * Filament's usual `$record` / `$state` injections.
 */
trait HasTypography
{
    protected bool | Closure $isBold = false;

    protected bool | Closure $isItalic = false;

    protected bool | Closure $isUnderlined = false;

    protected bool | Closure $isStruckThrough = false;

    protected bool | Closure $isUppercase = false;

    protected bool | Closure $isLowercase = false;

    protected bool | Closure $isCapitalized = false;

    public function bold(bool | Closure $condition = true): static
    {
        $this->isBold = $condition;

        return $this;
    }

    public function italic(bool | Closure $condition = true): static
    {
        $this->isItalic = $condition;

        return $this;
    }

    public function underline(bool | Closure $condition = true): static
    {
        $this->isUnderlined = $condition;

        return $this;
    }

    public function strikethrough(bool | Closure $condition = true): static
    {
        $this->isStruckThrough = $condition;

        return $this;
    }

    public function uppercase(bool | Closure $condition = true): static
    {
        $this->isUppercase = $condition;

        return $this;
    }

    public function lowercase(bool | Closure $condition = true): static
    {
        $this->isLowercase = $condition;

        return $this;
    }

    public function capitalize(bool | Closure $condition = true): static
    {
        $this->isCapitalized = $condition;

        return $this;
    }

    public function isBold(): bool
    {
        return (bool) $this->evaluate($this->isBold);
    }

    public function isItalic(): bool
    {
        return (bool) $this->evaluate($this->isItalic);
    }

    public function isUnderlined(): bool
    {
        return (bool) $this->evaluate($this->isUnderlined);
    }

    public function isStruckThrough(): bool
    {
        return (bool) $this->evaluate($this->isStruckThrough);
    }

    public function isUppercase(): bool
    {
        return (bool) $this->evaluate($this->isUppercase);
    }

    public function isLowercase(): bool
    {
        return (bool) $this->evaluate($this->isLowercase);
    }

    public function isCapitalized(): bool
    {
        return (bool) $this->evaluate($this->isCapitalized);
    }

    /**
     * The CSS classes for every enabled typography modifier.
     *
     * @return array<string>
     */
    public function getTypographyClasses(): array
    {
        return array_keys(array_filter([
            'fi-adv-text-bold' => $this->isBold(),
            'fi-adv-text-italic' => $this->isItalic(),
            'fi-adv-text-underline' => $this->isUnderlined(),
            'fi-adv-text-strike' => $this->isStruckThrough(),
            'fi-adv-text-uppercase' => $this->isUppercase(),
            'fi-adv-text-lowercase' => $this->isLowercase(),
            'fi-adv-text-capitalize' => $this->isCapitalized(),
        ]));
    }
}
