<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\Country;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneNumberType;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Metadata\LibPhoneNumberProvider;

/**
 * The offline source of truth for all phone metadata: country catalog,
 * parsing, formatting, and example numbers.
 *
 * The package ships {@see LibPhoneNumberProvider}
 * backed by giggsey/libphonenumber-for-php, which resolves everything from
 * bundled data with zero network calls. Bind your own implementation to swap
 * the entire metadata layer — a leaner dataset, a different library, a test
 * double — without touching the field.
 */
interface PhoneMetadataProvider
{
    /**
     * Every supported country, each as a resolved {@see Country} whose display
     * name is localized to `$locale`.
     *
     * @return array<int, Country>
     */
    public function countries(?string $locale = null): array;

    /**
     * A single country by ISO alpha-2 code, or `null` if unsupported.
     */
    public function country(string $iso, ?string $locale = null): ?Country;

    /**
     * Parse an input into a fully-resolved {@see PhoneNumberData}. `$region`
     * is an optional hint for national-format input; self-describing input
     * (starting with `+`) is parsed without it. Never throws — unparseable
     * input yields {@see PhoneNumberData::empty()}.
     */
    public function parse(string $input, ?string $region = null): PhoneNumberData;

    /**
     * Format an already-parsed number into the requested shape, or `null` when
     * the number is not parseable.
     */
    public function format(PhoneNumberData $number, PhoneFormat $format): ?string;

    /**
     * An example number for a region and line type, in national format — used
     * for placeholders and the "e.g. …" hint.
     */
    public function exampleNumber(string $region, PhoneNumberType $type = PhoneNumberType::Mobile): ?string;

    /**
     * The E.164 calling code for a region, e.g. `44` for `GB`.
     */
    public function dialCode(string $region): ?int;
}
