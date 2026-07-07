<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums;

use libphonenumber\PhoneNumberFormat as LibFormat;

/**
 * A phone-number rendering shape, used both for how a number is stored (the
 * dehydrated value) and for how it is displayed.
 *
 * Each case is the single source of truth mapping our public, developer-facing
 * vocabulary onto libphonenumber's own {@see LibFormat} constants, so the rest
 * of the package never touches the underlying library enum directly.
 *
 *  - {@see E164} — `+14155552671`. Compact, unambiguous, round-trippable. The
 *    recommended storage format: the country is embedded, so no companion
 *    column is needed.
 *  - {@see International} — `+1 415-555-2671`. Human-readable, globally
 *    unambiguous.
 *  - {@see National} — `(415) 555-2671`. How a local would write it; loses the
 *    country, so it needs a stable default country to round-trip.
 *  - {@see Rfc3966} — `tel:+1-415-555-2671`. The `tel:` URI form, extension
 *    included, ideal for click-to-call links.
 *  - {@see Raw} — `14155552671`. Digits only, no punctuation, no `+`.
 */
enum PhoneFormat: string
{
    case E164 = 'e164';

    case International = 'international';

    case National = 'national';

    case Rfc3966 = 'rfc3966';

    case Raw = 'raw';

    /**
     * The libphonenumber format constant this case maps to, or `null` for
     * {@see Raw}, which is derived by stripping the E164 form rather than
     * formatted by the library.
     */
    public function toLibphonenumber(): ?LibFormat
    {
        return match ($this) {
            self::E164 => LibFormat::E164,
            self::International => LibFormat::INTERNATIONAL,
            self::National => LibFormat::NATIONAL,
            self::Rfc3966 => LibFormat::RFC3966,
            self::Raw => null,
        };
    }

    /**
     * Whether the country of origin is recoverable from a value stored in this
     * format. National and raw forms drop the calling code, so hydrating them
     * back requires a known default country.
     */
    public function isCountryRecoverable(): bool
    {
        return match ($this) {
            self::E164, self::International, self::Rfc3966 => true,
            self::National, self::Raw => false,
        };
    }
}
