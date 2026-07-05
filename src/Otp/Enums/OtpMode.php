<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums;

use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\OtpInput;

/**
 * The character policy of an
 * {@see OtpInput}.
 *
 * Each case is the single source of truth for how the code is validated and
 * typed, on both sides of the wire:
 *
 *  - {@see characterClass()} is a regex character-class *body* (no delimiters,
 *    no anchors) shared verbatim by the client (to filter keystrokes and
 *    pastes) and the server (to build the Laravel `regex:` rule), so the two
 *    can never disagree about what a valid character is.
 *  - {@see inputMode()} maps to the HTML `inputmode` attribute, so mobile
 *    keyboards open on the right layout.
 *  - {@see autoCapitalize()} prevents mobile keyboards from silently
 *    upper-casing an otherwise case-sensitive code.
 */
enum OtpMode: string
{
    /** Digits only — `0-9`. */
    case Numeric = 'numeric';

    /** Latin letters only — `A-Z`, `a-z`. */
    case Alphabetic = 'alphabetic';

    /** Letters and digits — `A-Z`, `a-z`, `0-9`. */
    case Alphanumeric = 'alphanumeric';

    /**
     * The regex character-class body describing a single valid character.
     * Kept anchor- and delimiter-free so it can be embedded in both a
     * JavaScript `RegExp` and a PHP `preg_*` pattern.
     */
    public function characterClass(): string
    {
        return match ($this) {
            self::Numeric => '0-9',
            self::Alphabetic => 'A-Za-z',
            self::Alphanumeric => 'A-Za-z0-9',
        };
    }

    /**
     * The HTML `inputmode` hint for mobile keyboards.
     */
    public function inputMode(): string
    {
        return $this === self::Numeric ? 'numeric' : 'text';
    }

    /**
     * Whether mobile keyboards may auto-capitalize. Always off: a numeric
     * code has no case, and an alphabetic/alphanumeric code is treated as
     * case-sensitive.
     */
    public function autoCapitalize(): string
    {
        return 'off';
    }

    /**
     * Whether a digit is a valid character in this mode — used by the
     * server-side "numeric" convenience without re-deriving the class.
     */
    public function allowsDigits(): bool
    {
        return $this !== self::Alphabetic;
    }
}
