<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Formatting;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\FormatsPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\PhoneMetadataProvider;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;

/**
 * The default {@see FormatsPhoneNumbers}: a thin adapter over the
 * {@see PhoneMetadataProvider} that adds one policy the provider deliberately
 * stays out of — what to return when a number can't be formatted.
 *
 * Unparseable input falls back to `$fallback` (usually the raw string), so a
 * half-typed or malformed number is shown as the user left it rather than
 * vanishing.
 */
class PhoneNumberFormatter implements FormatsPhoneNumbers
{
    public function __construct(
        protected PhoneMetadataProvider $provider,
    ) {}

    public function format(PhoneNumberData $number, PhoneFormat $format, ?string $fallback = null): ?string
    {
        $formatted = $this->provider->format($number, $format);

        if ($formatted !== null) {
            return $formatted;
        }

        return $fallback ?? ($number->raw !== '' ? $number->raw : null);
    }
}
