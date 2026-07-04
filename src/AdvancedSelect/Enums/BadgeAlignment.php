<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Enums;

/**
 * Where an option's badge sits on the label line.
 *
 *  - {@see Start} — right after the label (the default).
 *  - {@see End} — pushed to the far end of the row.
 */
enum BadgeAlignment: string
{
    case Start = 'start';

    case End = 'end';

    /**
     * Coerce a loose value (an enum, a string, or null) to a case, falling
     * back to {@see Start}.
     */
    public static function fromValue(self | string | null $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_string($value)) {
            return self::tryFrom(strtolower($value)) ?? self::Start;
        }

        return self::Start;
    }
}
