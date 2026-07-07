<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\DetectsCountry;

/**
 * A single strategy for choosing which country a fresh (empty) field starts on.
 *
 * The field runs an ordered list of these — see
 * {@see DetectsCountry} —
 * and the first one that yields an allowed ISO country wins. The order is the
 * developer's priority, so "authenticated user preference, then app locale,
 * then a hard default" is expressed simply as an array of cases.
 */
enum CountryDetectionStrategy: string
{
    /** Use the explicitly configured {@see defaultCountry}. */
    case DefaultCountry = 'default';

    /** Derive from Laravel's active application locale (e.g. `en_GB` → `GB`). */
    case AppLocale = 'app_locale';

    /** Ask the resolved authenticated user (see `countryResolver`) — resolved
     *  through a developer callback so the package stays storage-agnostic. */
    case UserPreference = 'user_preference';

    /** Invoke the developer-provided `detectCountryUsing()` callback, the
     *  extension point for GeoIP, request headers, tenancy, and the like. */
    case Callback = 'callback';

    /** Fall back to the configured {@see fallbackCountry}, always last. */
    case Fallback = 'fallback';
}
