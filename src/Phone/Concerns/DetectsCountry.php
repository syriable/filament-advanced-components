<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\CountryDetectionStrategy;

/**
 * Chooses the country a fresh, empty field starts on.
 *
 * A number that already has a value dictates its own country (the calling code
 * is in the E.164 string); this concern only matters when there is nothing to
 * derive from. It runs an ordered list of {@see CountryDetectionStrategy} cases
 * and takes the first that yields an *available* country — so restrictions from
 * {@see HasCountries} are always respected:
 *
 * ```php
 * ->detectCountry([
 *     CountryDetectionStrategy::UserPreference,
 *     CountryDetectionStrategy::AppLocale,
 *     CountryDetectionStrategy::Fallback,
 * ])
 * ->countryResolver(fn () => auth()->user()?->country)   // user preference
 * ->detectCountryUsing(fn () => geoip(request()->ip())->iso_code); // callback
 * ```
 *
 * Every strategy is optional and side-effect free, so GeoIP, request headers,
 * or tenancy plug in through {@see detectCountryUsing()} without the package
 * taking a dependency on any of them.
 */
trait DetectsCountry
{
    protected string | Closure | null $defaultCountry = null;

    protected string | Closure | null $fallbackCountry = 'US';

    /** @var array<int, CountryDetectionStrategy>|Closure */
    protected array | Closure $detectionStrategies = [CountryDetectionStrategy::DefaultCountry, CountryDetectionStrategy::Fallback];

    protected ?Closure $countryResolver = null;

    protected ?Closure $countryDetector = null;

    /**
     * The explicit starting country, highest-priority when
     * {@see CountryDetectionStrategy::DefaultCountry} runs.
     */
    public function defaultCountry(string | Closure | null $iso): static
    {
        $this->defaultCountry = $iso;

        return $this;
    }

    /**
     * The last-resort country, used when every other strategy comes up empty.
     * Defaults to `US`; pass `null` to allow "no country".
     */
    public function fallbackCountry(string | Closure | null $iso): static
    {
        $this->fallbackCountry = $iso;

        return $this;
    }

    /**
     * The ordered detection pipeline. The first strategy that yields an allowed
     * country wins.
     *
     * @param  array<int, CountryDetectionStrategy>|Closure  $strategies
     */
    public function detectCountry(array | Closure $strategies): static
    {
        $this->detectionStrategies = $strategies;

        return $this;
    }

    /**
     * Supplies the country for {@see CountryDetectionStrategy::UserPreference} —
     * typically the authenticated user's stored country. Keeps the package
     * unaware of your user schema.
     */
    public function countryResolver(Closure $resolver): static
    {
        $this->countryResolver = $resolver;

        return $this;
    }

    /**
     * Supplies the country for {@see CountryDetectionStrategy::Callback} — the
     * extension point for GeoIP, `Accept-Language`, tenancy, and anything else.
     */
    public function detectCountryUsing(Closure $detector): static
    {
        $this->countryDetector = $detector;

        return $this;
    }

    public function getDefaultCountry(): ?string
    {
        return $this->normalizeIso($this->evaluate($this->defaultCountry));
    }

    public function getFallbackCountry(): ?string
    {
        return $this->normalizeIso($this->evaluate($this->fallbackCountry));
    }

    /**
     * Run the pipeline and return the first allowed country it produces, or the
     * first available country as an absolute last resort so the selector is
     * never empty-headed.
     */
    public function resolveInitialCountry(): ?string
    {
        /** @var array<int, CountryDetectionStrategy> $strategies */
        $strategies = $this->evaluate($this->detectionStrategies);

        foreach ($strategies as $strategy) {
            $iso = $this->runStrategy($strategy);

            if ($iso !== null && $this->isCountryAvailable($iso)) {
                return $iso;
            }
        }

        return $this->getAvailableCountries()[0]->iso ?? null;
    }

    protected function runStrategy(CountryDetectionStrategy $strategy): ?string
    {
        return match ($strategy) {
            CountryDetectionStrategy::DefaultCountry => $this->getDefaultCountry(),
            CountryDetectionStrategy::AppLocale => $this->countryFromLocale(app()->getLocale()),
            CountryDetectionStrategy::UserPreference => $this->countryResolver
                ? $this->normalizeIso($this->evaluate($this->countryResolver))
                : null,
            CountryDetectionStrategy::Callback => $this->countryDetector
                ? $this->normalizeIso($this->evaluate($this->countryDetector))
                : null,
            CountryDetectionStrategy::Fallback => $this->getFallbackCountry(),
        };
    }

    /**
     * Extract a region from a locale string like `en_GB` or `pt-BR`. Returns
     * `null` for locales without a region subtag (e.g. bare `en`).
     */
    protected function countryFromLocale(string $locale): ?string
    {
        if (! preg_match('/[_-]([A-Za-z]{2})$/', $locale, $matches)) {
            return null;
        }

        return $this->normalizeIso($matches[1]);
    }

    protected function normalizeIso(mixed $iso): ?string
    {
        if (! is_string($iso) || $iso === '') {
            return null;
        }

        return strtoupper($iso);
    }
}
