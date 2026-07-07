<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Closure;

/**
 * The presentational extras layered onto the input: the example-number hint,
 * the detected line-type badge, and the copy/clear affordances.
 *
 * Each is independent and reactive — the example and type follow the selected
 * country and the number as they change, with no server round-trip. Placeholders
 * default to the selected country's example number ({@see placeholderFromCountry()}),
 * so the field always shows a plausible local number as a hint unless a fixed
 * placeholder is set.
 */
trait HasPhoneAppearance
{
    protected bool | Closure $showExample = false;

    protected bool | Closure $showType = false;

    protected bool | Closure $copyable = false;

    protected bool | Closure $clearable = false;

    protected bool | Closure $placeholderFromCountry = true;

    public function showExample(bool | Closure $condition = true): static
    {
        $this->showExample = $condition;

        return $this;
    }

    public function showType(bool | Closure $condition = true): static
    {
        $this->showType = $condition;

        return $this;
    }

    /**
     * Show a button that copies the normalized (storage-format) number to the
     * clipboard.
     */
    public function copyable(bool | Closure $condition = true): static
    {
        $this->copyable = $condition;

        return $this;
    }

    /**
     * Show a button that clears the number and returns focus to the input.
     */
    public function clearable(bool | Closure $condition = true): static
    {
        $this->clearable = $condition;

        return $this;
    }

    /**
     * Use the selected country's example number as the placeholder (default).
     * Disable to fall back to the field's own placeholder, or none.
     */
    public function placeholderFromCountry(bool | Closure $condition = true): static
    {
        $this->placeholderFromCountry = $condition;

        return $this;
    }

    public function shouldShowExample(): bool
    {
        return (bool) $this->evaluate($this->showExample);
    }

    public function shouldShowType(): bool
    {
        return (bool) $this->evaluate($this->showType);
    }

    public function isCopyable(): bool
    {
        return (bool) $this->evaluate($this->copyable);
    }

    public function isClearable(): bool
    {
        return (bool) $this->evaluate($this->clearable);
    }

    public function shouldUseCountryPlaceholder(): bool
    {
        return (bool) $this->evaluate($this->placeholderFromCountry);
    }
}
