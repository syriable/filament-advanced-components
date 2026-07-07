<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Validation;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\ValidatesPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\ValidationResult;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneNumberType;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneValidationError;

/**
 * The default {@see ValidatesPhoneNumbers}, applying the field's policy in a
 * fixed order and reporting the first thing that fails with a precise
 * {@see PhoneValidationError}:
 *
 *   1. parseable at all,
 *   2. a real region (not an orphan calling code),
 *   3. length (possible) — and, in strict mode, genuine validity,
 *   4. the country is permitted,
 *   5. the line type is permitted.
 *
 * A blank input always passes here: presence is the `required` rule's job, not
 * this validator's. Everything is decided from the offline
 * {@see PhoneNumberData}, so validation never touches the network.
 */
class PhoneNumberValidator implements ValidatesPhoneNumbers
{
    public function validate(
        PhoneNumberData $number,
        bool $strict = true,
        array $allowedTypes = [],
        array $allowedCountries = [],
    ): ValidationResult {
        if ($number->isBlank()) {
            return ValidationResult::pass();
        }

        if (! $number->isParsed()) {
            return ValidationResult::fail(PhoneValidationError::NotANumber);
        }

        if ($number->country === null) {
            return ValidationResult::fail(PhoneValidationError::InvalidCountry);
        }

        if (! $number->isPossible) {
            return ValidationResult::fail($this->lengthError($number));
        }

        if ($strict && ! $number->isValid) {
            return ValidationResult::fail(PhoneValidationError::Invalid);
        }

        if ($allowedCountries !== [] && ! in_array($number->country, $this->normalizeCountries($allowedCountries), true)) {
            return ValidationResult::fail(PhoneValidationError::CountryNotAllowed);
        }

        if ($allowedTypes !== [] && ! $this->matchesType($number->type, $allowedTypes)) {
            return ValidationResult::fail(PhoneValidationError::InvalidType);
        }

        return ValidationResult::pass();
    }

    /**
     * A length failure is either too short or too long; libphonenumber only
     * exposes "not possible", so we approximate the direction from the national
     * significant length when available, defaulting to "too short".
     */
    protected function lengthError(PhoneNumberData $number): PhoneValidationError
    {
        $nationalDigits = preg_replace('/\D/', '', (string) $number->national);

        // Most impossible numbers are short; treat an unusually long one as
        // too long so the message matches the mistake.
        return strlen((string) $nationalDigits) > 15
            ? PhoneValidationError::TooLong
            : PhoneValidationError::TooShort;
    }

    /**
     * Whether the number's type satisfies the allow-list. A field asking for
     * mobiles also accepts the ambiguous fixed-line-or-mobile type, since many
     * regions cannot tell the two apart.
     *
     * @param  array<int, PhoneNumberType>  $allowedTypes
     */
    protected function matchesType(PhoneNumberType $type, array $allowedTypes): bool
    {
        if (in_array($type, $allowedTypes, true)) {
            return true;
        }

        $wantsMobile = in_array(PhoneNumberType::Mobile, $allowedTypes, true);
        $wantsFixed = in_array(PhoneNumberType::FixedLine, $allowedTypes, true);

        return $type === PhoneNumberType::FixedLineOrMobile && ($wantsMobile || $wantsFixed);
    }

    /**
     * @param  array<int, string>  $countries
     * @return array<int, string>
     */
    protected function normalizeCountries(array $countries): array
    {
        return array_map('strtoupper', $countries);
    }
}
