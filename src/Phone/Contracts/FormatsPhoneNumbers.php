<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;

/**
 * Turns a parsed number into a display or storage string in a chosen
 * {@see PhoneFormat}.
 *
 * Separated from the metadata provider so an application can override *only*
 * formatting — a house style, a legacy column shape — while keeping the
 * bundled parsing and country data. Bind a replacement to
 * {@see FormatsPhoneNumbers} globally, or set one per field with
 * `->formatter()`.
 */
interface FormatsPhoneNumbers
{
    /**
     * Format `$number` as `$format`, or return `$fallback` when the number is
     * not parseable (typically the raw input, so nothing is silently dropped).
     */
    public function format(PhoneNumberData $number, PhoneFormat $format, ?string $fallback = null): ?string;
}
