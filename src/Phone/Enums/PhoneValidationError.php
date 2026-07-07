<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\ValidationResult;

/**
 * Why a submitted number failed validation.
 *
 * Returned inside a {@see ValidationResult}
 * and turned into a user-facing message through the `phone-input.validation.*`
 * language lines, so the reason shown ("too short", "not a valid mobile
 * number") is precise rather than a generic "invalid phone number".
 */
enum PhoneValidationError: string
{
    /** The input could not be parsed as a phone number at all. */
    case NotANumber = 'not_a_number';

    /** Parsed, but the calling code belongs to no known region. */
    case InvalidCountry = 'invalid_country';

    /** Recognizable, but shorter than any valid number for the region. */
    case TooShort = 'too_short';

    /** Recognizable, but longer than any valid number for the region. */
    case TooLong = 'too_long';

    /** A plausible length, but not an actually assigned/valid number. */
    case Invalid = 'invalid';

    /** Valid, but the wrong line type for the field (e.g. a landline where
     *  only mobiles are accepted). */
    case InvalidType = 'invalid_type';

    /** Valid, but the country is not permitted by the field's restrictions. */
    case CountryNotAllowed = 'country_not_allowed';

    /**
     * The `phone-input.validation.*` translation key for this error.
     */
    public function translationKey(): string
    {
        return 'filament-advanced-components::phone-input.validation.' . $this->value;
    }
}
