<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;

/**
 * The two formats a phone field juggles, kept deliberately separate:
 *
 *  - the **storage** format ({@see storeAs()}) — what lands in the database.
 *    E.164 by default, because the country is embedded and it round-trips
 *    cleanly.
 *  - the **display** format ({@see displayAs()}) — how a saved number reads
 *    when the field re-hydrates. International by default.
 *
 * Both accept a {@see PhoneFormat} (or a closure), and each has a set of terse
 * shortcuts (`->storeE164()`, `->displayNational()`, …) for the common cases.
 */
trait HasPhoneFormatting
{
    protected PhoneFormat | Closure $storageFormat = PhoneFormat::E164;

    protected PhoneFormat | Closure $displayFormat = PhoneFormat::International;

    public function storeAs(PhoneFormat | Closure $format): static
    {
        $this->storageFormat = $format;

        return $this;
    }

    public function displayAs(PhoneFormat | Closure $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    public function storeE164(): static
    {
        return $this->storeAs(PhoneFormat::E164);
    }

    public function storeNational(): static
    {
        return $this->storeAs(PhoneFormat::National);
    }

    public function storeInternational(): static
    {
        return $this->storeAs(PhoneFormat::International);
    }

    public function storeRfc3966(): static
    {
        return $this->storeAs(PhoneFormat::Rfc3966);
    }

    public function storeRaw(): static
    {
        return $this->storeAs(PhoneFormat::Raw);
    }

    public function displayNational(): static
    {
        return $this->displayAs(PhoneFormat::National);
    }

    public function displayInternational(): static
    {
        return $this->displayAs(PhoneFormat::International);
    }

    public function displayE164(): static
    {
        return $this->displayAs(PhoneFormat::E164);
    }

    public function getStorageFormat(): PhoneFormat
    {
        return $this->evaluate($this->storageFormat);
    }

    public function getDisplayFormat(): PhoneFormat
    {
        return $this->evaluate($this->displayFormat);
    }
}
