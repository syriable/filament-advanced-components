<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Normalization;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\FormatsPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\NormalizesPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;

/**
 * The default {@see NormalizesPhoneNumbers}: persist a parseable number in the
 * field's configured storage format, and preserve an unparseable-but-non-blank
 * input verbatim so validation can flag it rather than silently discarding what
 * the user typed.
 *
 * A blank input normalizes to `null`, so an optional, empty field stores
 * nothing and validates as "not present".
 */
class PhoneNumberNormalizer implements NormalizesPhoneNumbers
{
    public function __construct(
        protected FormatsPhoneNumbers $formatter,
    ) {}

    public function normalize(PhoneNumberData $number, PhoneFormat $storageFormat): ?string
    {
        if ($number->isBlank()) {
            return null;
        }

        // Parseable: store the requested format. Unparseable: keep the raw
        // input so a validation rule can reject it visibly.
        return $this->formatter->format($number, $storageFormat, $number->raw);
    }
}
