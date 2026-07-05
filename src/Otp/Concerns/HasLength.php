<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns;

use Closure;

/**
 * The number of cells (characters) an OTP code is made of. Lazily evaluated
 * and floored at 1, so a misconfigured `length(0)` can never render an empty,
 * un-fillable field.
 */
trait HasLength
{
    protected int | Closure $length = 6;

    /**
     * The number of characters in the code. Defaults to 6.
     */
    public function length(int | Closure $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function getLength(): int
    {
        return max(1, (int) $this->evaluate($this->length));
    }
}
