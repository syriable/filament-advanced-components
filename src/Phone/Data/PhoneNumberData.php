<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Data;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneNumberType;

/**
 * The immutable, fully-resolved result of parsing one phone number.
 *
 * This is the package's own value object: it carries every representation the
 * views, formatter, normalizer, and validator need, so nothing downstream ever
 * has to reach back into libphonenumber. An unparseable input still produces a
 * {@see PhoneNumberData} — an {@see empty} one — so callers never juggle
 * `null`s: they inspect {@see $isValid}/{@see $isPossible} instead.
 */
readonly class PhoneNumberData
{
    /**
     * @param  string  $raw  The original input, untouched.
     * @param  string|null  $e164  E.164 form (`+14155552671`) when parseable.
     * @param  string|null  $national  National form (`(415) 555-2671`).
     * @param  string|null  $international  International form (`+1 415-555-2671`).
     * @param  string|null  $rfc3966  RFC 3966 `tel:` URI form.
     * @param  string|null  $country  ISO alpha-2 region, e.g. `US`.
     * @param  int|null  $dialCode  Calling code, e.g. `1`.
     * @param  string|null  $extension  Dialing extension, digits only.
     * @param  bool  $isValid  A real, assigned, valid number.
     * @param  bool  $isPossible  A plausible length for its region.
     */
    public function __construct(
        public string $raw,
        public ?string $e164,
        public ?string $national,
        public ?string $international,
        public ?string $rfc3966,
        public ?string $country,
        public ?int $dialCode,
        public ?string $extension,
        public PhoneNumberType $type,
        public bool $isValid,
        public bool $isPossible,
    ) {}

    /**
     * The value object for an input that could not be parsed at all — every
     * representation is `null`, the type is {@see PhoneNumberType::Unknown},
     * and it is neither valid nor possible.
     */
    public static function empty(string $raw = ''): self
    {
        return new self(
            raw: $raw,
            e164: null,
            national: null,
            international: null,
            rfc3966: null,
            country: null,
            dialCode: null,
            extension: null,
            type: PhoneNumberType::Unknown,
            isValid: false,
            isPossible: false,
        );
    }

    /**
     * Whether the input was blank — no number was entered.
     */
    public function isBlank(): bool
    {
        return trim($this->raw) === '';
    }

    /**
     * Whether libphonenumber managed to parse the input into a number, valid
     * or not. False for blanks and true garbage.
     */
    public function isParsed(): bool
    {
        return $this->e164 !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'raw' => $this->raw,
            'e164' => $this->e164,
            'national' => $this->national,
            'international' => $this->international,
            'rfc3966' => $this->rfc3966,
            'country' => $this->country,
            'dialCode' => $this->dialCode,
            'extension' => $this->extension,
            'type' => $this->type->value,
            'isValid' => $this->isValid,
            'isPossible' => $this->isPossible,
        ];
    }
}
