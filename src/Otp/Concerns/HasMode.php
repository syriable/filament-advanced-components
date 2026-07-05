<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpMode;

/**
 * The character policy of the code — numeric (the default), alphabetic, or
 * alphanumeric. The chosen {@see OtpMode} drives client-side keystroke
 * filtering, the mobile `inputmode`, and the server-side validation rule
 * from a single source of truth.
 */
trait HasMode
{
    protected OtpMode | string | Closure $mode = OtpMode::Numeric;

    public function mode(OtpMode | string | Closure $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Digits only. The default.
     */
    public function numeric(bool | Closure $condition = true): static
    {
        return $this->mode(fn (): OtpMode => $this->evaluate($condition) ? OtpMode::Numeric : OtpMode::Numeric);
    }

    /**
     * Latin letters only. A falsy condition falls back to the default
     * numeric mode.
     */
    public function alphabetic(bool | Closure $condition = true): static
    {
        return $this->mode(fn (): OtpMode => $this->evaluate($condition) ? OtpMode::Alphabetic : OtpMode::Numeric);
    }

    /**
     * Letters and digits. A falsy condition falls back to the default
     * numeric mode.
     */
    public function alphanumeric(bool | Closure $condition = true): static
    {
        return $this->mode(fn (): OtpMode => $this->evaluate($condition) ? OtpMode::Alphanumeric : OtpMode::Numeric);
    }

    public function getMode(): OtpMode
    {
        $mode = $this->evaluate($this->mode);

        if ($mode instanceof OtpMode) {
            return $mode;
        }

        return OtpMode::tryFrom((string) $mode) ?? OtpMode::Numeric;
    }
}
