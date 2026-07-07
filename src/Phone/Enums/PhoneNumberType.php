<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums;

use libphonenumber\PhoneNumberType as LibType;

/**
 * The line type of a parsed number — mobile, fixed line, VOIP, and so on.
 *
 * This is a stable, package-owned mirror of libphonenumber's own
 * {@see LibType} enum, so callers, validation rules, and translations depend on
 * our vocabulary rather than the library's. {@see fromLib()} adapts the
 * library value, collapsing its handful of rarely-used cases into
 * {@see Unknown} where a public distinction adds no value.
 */
enum PhoneNumberType: string
{
    case Mobile = 'mobile';

    case FixedLine = 'fixed_line';

    case FixedLineOrMobile = 'fixed_line_or_mobile';

    case TollFree = 'toll_free';

    case PremiumRate = 'premium_rate';

    case SharedCost = 'shared_cost';

    case Voip = 'voip';

    case PersonalNumber = 'personal_number';

    case Pager = 'pager';

    case Uan = 'uan';

    case Voicemail = 'voicemail';

    case Unknown = 'unknown';

    /**
     * Adapt a libphonenumber {@see LibType} into our enum. Unrecognized or
     * short-code types fold into {@see Unknown}.
     */
    public static function fromLib(LibType $type): self
    {
        return match ($type) {
            LibType::MOBILE => self::Mobile,
            LibType::FIXED_LINE => self::FixedLine,
            LibType::FIXED_LINE_OR_MOBILE => self::FixedLineOrMobile,
            LibType::TOLL_FREE => self::TollFree,
            LibType::PREMIUM_RATE => self::PremiumRate,
            LibType::SHARED_COST => self::SharedCost,
            LibType::VOIP => self::Voip,
            LibType::PERSONAL_NUMBER => self::PersonalNumber,
            LibType::PAGER => self::Pager,
            LibType::UAN => self::Uan,
            LibType::VOICEMAIL => self::Voicemail,
            default => self::Unknown,
        };
    }

    /**
     * The translation key for this type's human label, resolved against the
     * package's `phone-input.types.*` language lines.
     */
    public function label(): string
    {
        return __('filament-advanced-components::phone-input.types.' . $this->value);
    }

    /**
     * Whether this type denotes a number a person can be reached on directly —
     * used by the mobile/fixed convenience validation helpers.
     */
    public function isReachableLine(): bool
    {
        return match ($this) {
            self::Mobile, self::FixedLine, self::FixedLineOrMobile, self::Voip,
            self::PersonalNumber, self::Uan => true,
            default => false,
        };
    }
}
