<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Closure;

/**
 * The look and behavior of the country selector attached to the input: whether
 * it exists at all, whether it shows flags and dial codes, and how its search
 * behaves.
 *
 * Turn the selector off with {@see withoutCountrySelector()} for a
 * single-country field (the field then behaves like a plain national input for
 * the default country). The search box, debounce, and flag/dial-code chrome are
 * all independently toggleable.
 */
trait HasCountrySelector
{
    protected bool | Closure $hasCountrySelector = true;

    protected bool | Closure $showFlags = true;

    protected bool | Closure $showDialCode = true;

    protected bool | Closure $enableSearch = true;

    protected int | Closure $searchDebounce = 150;

    protected string | Closure | null $countryLocale = null;

    public function countrySelector(bool | Closure $condition = true): static
    {
        $this->hasCountrySelector = $condition;

        return $this;
    }

    public function withoutCountrySelector(): static
    {
        return $this->countrySelector(false);
    }

    public function showFlags(bool | Closure $condition = true): static
    {
        $this->showFlags = $condition;

        return $this;
    }

    public function showDialCode(bool | Closure $condition = true): static
    {
        $this->showDialCode = $condition;

        return $this;
    }

    public function enableSearch(bool | Closure $condition = true): static
    {
        $this->enableSearch = $condition;

        return $this;
    }

    /**
     * Milliseconds to debounce the country search input. Floors at 0.
     */
    public function searchDebounce(int | Closure $milliseconds): static
    {
        $this->searchDebounce = $milliseconds;

        return $this;
    }

    /**
     * Override the locale used for country display names. Defaults to the
     * application locale, so names follow the panel's language automatically.
     */
    public function countryLocale(string | Closure | null $locale): static
    {
        $this->countryLocale = $locale;

        return $this;
    }

    public function hasCountrySelector(): bool
    {
        return (bool) $this->evaluate($this->hasCountrySelector);
    }

    public function shouldShowFlags(): bool
    {
        return (bool) $this->evaluate($this->showFlags);
    }

    public function shouldShowDialCode(): bool
    {
        return (bool) $this->evaluate($this->showDialCode);
    }

    public function isSearchEnabled(): bool
    {
        return (bool) $this->evaluate($this->enableSearch);
    }

    public function getSearchDebounce(): int
    {
        return max(0, (int) $this->evaluate($this->searchDebounce));
    }

    public function getCountryLocale(): ?string
    {
        return $this->evaluate($this->countryLocale);
    }
}
