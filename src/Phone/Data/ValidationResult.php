<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Data;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneValidationError;

/**
 * The outcome of validating one number against a field's rules: a pass, or a
 * fail carrying the precise {@see PhoneValidationError} reason.
 *
 * Keeping the reason as structured data (rather than a pre-baked string) lets
 * the Laravel rule translate it with the right attribute name and lets tests
 * assert on the reason, not on copy.
 */
readonly class ValidationResult
{
    private function __construct(
        public bool $passes,
        public ?PhoneValidationError $error,
    ) {}

    public static function pass(): self
    {
        return new self(true, null);
    }

    public static function fail(PhoneValidationError $error): self
    {
        return new self(false, $error);
    }

    public function fails(): bool
    {
        return ! $this->passes;
    }
}
