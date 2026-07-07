<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Metadata;

use Giggsey\Locale\Locale;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat as LibFormat;
use libphonenumber\PhoneNumberType as LibType;
use libphonenumber\PhoneNumberUtil;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\PhoneMetadataProvider;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\Country;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneNumberType;

/**
 * The default {@see PhoneMetadataProvider}, backed by
 * giggsey/libphonenumber-for-php. Every answer — country list, calling codes,
 * parsing, formatting, example numbers — comes from the library's bundled,
 * offline dataset, so the field validates and formats without a single network
 * request.
 *
 * The country catalog is the one genuinely expensive thing to assemble (a few
 * hundred regions, each localized), so it is memoized per locale for the life
 * of the (singleton) instance. Parsing is cheap and left un-cached.
 */
class LibPhoneNumberProvider implements PhoneMetadataProvider
{
    /**
     * Per-locale country catalogs, keyed by locale string.
     *
     * @var array<string, array<int, Country>>
     */
    protected array $countryCache = [];

    /**
     * Memoized national example numbers, keyed by "ISO:type" — example data is
     * locale-independent, so one cache serves every render.
     *
     * @var array<string, string|null>
     */
    protected array $exampleCache = [];

    protected PhoneNumberUtil $util;

    /**
     * @param  PhoneNumberUtil|null  $util  Injectable for testing; defaults to
     *                                      the library's shared singleton.
     */
    public function __construct(?PhoneNumberUtil $util = null)
    {
        $this->util = $util ?? PhoneNumberUtil::getInstance();
    }

    public function countries(?string $locale = null): array
    {
        $locale = $this->normalizeLocale($locale);

        return $this->countryCache[$locale] ??= $this->buildCountries($locale);
    }

    public function country(string $iso, ?string $locale = null): ?Country
    {
        $iso = strtoupper($iso);

        foreach ($this->countries($locale) as $country) {
            if ($country->iso === $iso) {
                return $country;
            }
        }

        return null;
    }

    public function parse(string $input, ?string $region = null): PhoneNumberData
    {
        $input = trim($input);

        if ($input === '') {
            return PhoneNumberData::empty();
        }

        try {
            $number = $this->util->parse($input, $region);
        } catch (NumberParseException) {
            return PhoneNumberData::empty($input);
        }

        return $this->hydrate($input, $number);
    }

    public function format(PhoneNumberData $number, PhoneFormat $format): ?string
    {
        return match ($format) {
            PhoneFormat::E164 => $number->e164,
            PhoneFormat::International => $number->international,
            PhoneFormat::National => $number->national,
            PhoneFormat::Rfc3966 => $number->rfc3966,
            PhoneFormat::Raw => $number->e164 === null ? null : ltrim($number->e164, '+'),
        };
    }

    public function exampleNumber(string $region, PhoneNumberType $type = PhoneNumberType::Mobile): ?string
    {
        $key = strtoupper($region) . ':' . $type->value;

        if (array_key_exists($key, $this->exampleCache)) {
            return $this->exampleCache[$key];
        }

        $example = $this->util->getExampleNumberForType(strtoupper($region), $this->toLibType($type));

        return $this->exampleCache[$key] = $example === null
            ? null
            : $this->util->format($example, LibFormat::NATIONAL);
    }

    public function dialCode(string $region): ?int
    {
        $code = $this->util->getCountryCodeForRegion(strtoupper($region));

        return $code === 0 ? null : $code;
    }

    /**
     * Turn a parsed {@see PhoneNumber} into the immutable value object,
     * formatting every representation once.
     */
    protected function hydrate(string $raw, PhoneNumber $number): PhoneNumberData
    {
        $region = $this->util->getRegionCodeForNumber($number);
        $extension = $number->getExtension();

        return new PhoneNumberData(
            raw: $raw,
            e164: $this->util->format($number, LibFormat::E164),
            national: $this->util->format($number, LibFormat::NATIONAL),
            international: $this->util->format($number, LibFormat::INTERNATIONAL),
            rfc3966: $this->util->format($number, LibFormat::RFC3966),
            country: $region,
            dialCode: $number->getCountryCode(),
            extension: ($extension === null || $extension === '') ? null : $extension,
            type: PhoneNumberType::fromLib($this->util->getNumberType($number)),
            isValid: $this->util->isValidNumber($number),
            isPossible: $this->util->isPossibleNumber($number),
        );
    }

    /**
     * Assemble and sort the full country catalog for a locale.
     *
     * @return array<int, Country>
     */
    protected function buildCountries(string $locale): array
    {
        $countries = [];

        foreach ($this->util->getSupportedRegions() as $iso) {
            $dialCode = $this->util->getCountryCodeForRegion($iso);

            if ($dialCode === 0) {
                continue;
            }

            $countries[] = Country::make(
                iso: $iso,
                name: $this->displayName($iso, $locale),
                dialCode: $dialCode,
            );
        }

        usort($countries, static fn (Country $a, Country $b): int => strcmp($a->name, $b->name));

        return $countries;
    }

    /**
     * The localized country name, falling back to the ISO code if the locale
     * data has no entry for it.
     */
    protected function displayName(string $iso, string $locale): string
    {
        $name = Locale::getDisplayRegion('-' . strtoupper($iso), $locale);

        return $name !== '' ? $name : strtoupper($iso);
    }

    protected function normalizeLocale(?string $locale): string
    {
        $locale = $locale ?? app()->getLocale();

        return str_replace('-', '_', $locale);
    }

    protected function toLibType(PhoneNumberType $type): LibType
    {
        return match ($type) {
            PhoneNumberType::Mobile => LibType::MOBILE,
            PhoneNumberType::FixedLine => LibType::FIXED_LINE,
            PhoneNumberType::FixedLineOrMobile => LibType::FIXED_LINE_OR_MOBILE,
            PhoneNumberType::TollFree => LibType::TOLL_FREE,
            PhoneNumberType::PremiumRate => LibType::PREMIUM_RATE,
            PhoneNumberType::SharedCost => LibType::SHARED_COST,
            PhoneNumberType::Voip => LibType::VOIP,
            PhoneNumberType::PersonalNumber => LibType::PERSONAL_NUMBER,
            PhoneNumberType::Pager => LibType::PAGER,
            PhoneNumberType::Uan => LibType::UAN,
            PhoneNumberType::Voicemail => LibType::VOICEMAIL,
            PhoneNumberType::Unknown => LibType::UNKNOWN,
        };
    }
}
