<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\ValidatesPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneNumberType;

/**
 * The field's validation policy: how strict to be, and which line types to
 * accept.
 *
 * By default a field is **strict** — it requires a genuinely valid, assigned
 * number, not merely one of plausible length. {@see lenient()} relaxes that to
 * accept any "possible" number, useful for directories importing messy data.
 * {@see validateTypes()} (and the {@see mobileOnly()}/{@see fixedLineOnly()}
 * shortcuts) restrict the acceptable line types.
 *
 * All of this is enforced server-side by the {@see ValidatesPhoneNumbers}
 * regardless of anything the client allowed.
 */
trait HasPhoneValidation
{
    protected bool | Closure $strictValidation = true;

    /** @var array<int, PhoneNumberType>|Closure */
    protected array | Closure $allowedTypes = [];

    /**
     * Require a fully valid number (default). Pass `false` for {@see lenient()}.
     */
    public function strictValidation(bool | Closure $condition = true): static
    {
        $this->strictValidation = $condition;

        return $this;
    }

    /**
     * Accept any number of plausible length, even if not a known-valid one —
     * the inverse of {@see strictValidation()}.
     */
    public function lenient(bool $condition = true): static
    {
        return $this->strictValidation(! $condition);
    }

    /**
     * Restrict acceptable line types (mobile, fixed line, VOIP, …). An empty
     * list accepts any type.
     *
     * @param  array<int, PhoneNumberType>|Closure  $types
     */
    public function validateTypes(array | Closure $types): static
    {
        $this->allowedTypes = $types;

        return $this;
    }

    /**
     * Accept only mobile numbers (and the ambiguous fixed-line-or-mobile type
     * many regions cannot distinguish).
     */
    public function mobileOnly(): static
    {
        return $this->validateTypes([PhoneNumberType::Mobile]);
    }

    /**
     * Accept only fixed-line numbers (and fixed-line-or-mobile).
     */
    public function fixedLineOnly(): static
    {
        return $this->validateTypes([PhoneNumberType::FixedLine]);
    }

    public function isStrictValidation(): bool
    {
        return (bool) $this->evaluate($this->strictValidation);
    }

    /**
     * @return array<int, PhoneNumberType>
     */
    public function getAllowedTypes(): array
    {
        $types = $this->evaluate($this->allowedTypes);

        return is_array($types) ? array_values($types) : [];
    }
}
