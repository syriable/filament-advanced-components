<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\Country;

/**
 * The country catalog policy: which countries the selector offers, and which
 * float to the top.
 *
 * `allowedCountries()` and `blockedCountries()` are an allow/deny pair —
 * allow-list wins when both are set — and `preferredCountries()` pins a handful
 * to the top of the list (in the given order) while leaving the rest sorted by
 * localized name. All three accept ISO codes or closures, so the catalog can
 * depend on the record, the tenant, or the authenticated user.
 *
 * The resolved list is assembled once per render by {@see getAvailableCountries()}
 * and handed to both the Blade view and the client as plain data.
 */
trait HasCountries
{
    /** @var array<int, string>|Closure */
    protected array | Closure $allowedCountries = [];

    /** @var array<int, string>|Closure */
    protected array | Closure $blockedCountries = [];

    /** @var array<int, string>|Closure */
    protected array | Closure $preferredCountries = [];

    /**
     * Restrict the selector to these ISO codes. An empty list means "all
     * supported countries".
     *
     * @param  array<int, string>|Closure  $countries
     */
    public function allowedCountries(array | Closure $countries): static
    {
        $this->allowedCountries = $countries;

        return $this;
    }

    /**
     * Alias of {@see allowedCountries()} that reads better at some call sites.
     *
     * @param  array<int, string>|Closure  $countries
     */
    public function onlyCountries(array | Closure $countries): static
    {
        return $this->allowedCountries($countries);
    }

    /**
     * Remove these ISO codes from the selector. Ignored for any country also
     * present in an {@see allowedCountries()} allow-list.
     *
     * @param  array<int, string>|Closure  $countries
     */
    public function blockedCountries(array | Closure $countries): static
    {
        $this->blockedCountries = $countries;

        return $this;
    }

    /**
     * Pin these ISO codes to the top of the list, in the order given.
     *
     * @param  array<int, string>|Closure  $countries
     */
    public function preferredCountries(array | Closure $countries): static
    {
        $this->preferredCountries = $countries;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedCountries(): array
    {
        return $this->normalizeIsoList($this->evaluate($this->allowedCountries));
    }

    /**
     * @return array<int, string>
     */
    public function getBlockedCountries(): array
    {
        return $this->normalizeIsoList($this->evaluate($this->blockedCountries));
    }

    /**
     * @return array<int, string>
     */
    public function getPreferredCountries(): array
    {
        return $this->normalizeIsoList($this->evaluate($this->preferredCountries));
    }

    /**
     * The final, ordered list the selector shows: allow/deny applied,
     * preferred countries pinned to the top in their configured order, the rest
     * left in the provider's localized alphabetical order.
     *
     * @return array<int, Country>
     */
    public function getAvailableCountries(): array
    {
        $allowed = $this->getAllowedCountries();
        $blocked = $this->getBlockedCountries();
        $preferred = $this->getPreferredCountries();

        $countries = array_filter(
            $this->getMetadataProvider()->countries($this->getCountryLocale()),
            static function (Country $country) use ($allowed, $blocked): bool {
                if ($allowed !== [] && ! in_array($country->iso, $allowed, true)) {
                    return false;
                }

                if ($allowed === [] && in_array($country->iso, $blocked, true)) {
                    return false;
                }

                return true;
            },
        );

        return $this->pinPreferred(array_values($countries), $preferred);
    }

    /**
     * Whether a given ISO code survives the allow/deny policy — used when
     * validating a detected or selected country.
     */
    public function isCountryAvailable(string $iso): bool
    {
        $iso = strtoupper($iso);

        foreach ($this->getAvailableCountries() as $country) {
            if ($country->iso === $iso) {
                return true;
            }
        }

        return false;
    }

    /**
     * Move preferred countries to the front, in the order the developer listed
     * them, and tag them so the view can render a divider.
     *
     * @param  array<int, Country>  $countries
     * @param  array<int, string>  $preferred
     * @return array<int, Country>
     */
    protected function pinPreferred(array $countries, array $preferred): array
    {
        if ($preferred === []) {
            return $countries;
        }

        $byIso = [];

        foreach ($countries as $country) {
            $byIso[$country->iso] = $country;
        }

        $pinned = [];

        foreach ($preferred as $iso) {
            if (isset($byIso[$iso])) {
                $pinned[] = $byIso[$iso]->withPreferred();
                unset($byIso[$iso]);
            }
        }

        return array_merge($pinned, array_values($byIso));
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeIsoList(mixed $countries): array
    {
        if (! is_array($countries)) {
            return [];
        }

        return array_values(array_map(
            static fn (string $iso): string => strtoupper($iso),
            $countries,
        ));
    }
}
