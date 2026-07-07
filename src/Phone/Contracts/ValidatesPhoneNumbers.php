<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\ValidationResult;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneNumberType;

/**
 * Validates a parsed number against a field's policy — strictness, allowed
 * line types, and permitted countries — returning a structured
 * {@see ValidationResult} rather than a boolean, so the failure reason survives
 * to the message layer.
 *
 * This is the server-side authority: whatever the client permitted, the number
 * is checked again here. Bind a replacement to relax or tighten the global
 * policy, or set one per field with `->validator()`.
 */
interface ValidatesPhoneNumbers
{
    /**
     * @param  bool  $strict  When true, require a genuinely valid number; when
     *                        false, accept any "possible" (right-length) number.
     * @param  array<int, PhoneNumberType>  $allowedTypes  Permitted line types;
     *                                                     empty means any type is accepted.
     * @param  array<int, string>  $allowedCountries  Permitted ISO codes; empty
     *                                                means any country is accepted.
     */
    public function validate(
        PhoneNumberData $number,
        bool $strict = true,
        array $allowedTypes = [],
        array $allowedCountries = [],
    ): ValidationResult;
}
