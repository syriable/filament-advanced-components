<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\FormatsPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\NormalizesPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\PhoneMetadataProvider;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\ValidatesPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Formatting\PhoneNumberFormatter;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Normalization\PhoneNumberNormalizer;

/**
 * Resolves the four swappable collaborators — metadata provider, formatter,
 * normalizer, validator — with a consistent precedence:
 *
 *   per-field override  →  container binding  →  package default.
 *
 * Set one per field (`->metadataProvider(...)`, `->formatter(...)`,
 * `->normalizer(...)`, `->validator(...)`) for a one-off, or bind the contract
 * in a service provider to change it everywhere. The formatter and normalizer
 * default to instances wired to whichever provider won, so overriding just the
 * provider automatically flows through the whole chain.
 */
trait InteractsWithPhoneServices
{
    protected ?PhoneMetadataProvider $metadataProvider = null;

    protected ?FormatsPhoneNumbers $formatter = null;

    protected ?NormalizesPhoneNumbers $normalizer = null;

    protected ?ValidatesPhoneNumbers $validator = null;

    public function metadataProvider(PhoneMetadataProvider $provider): static
    {
        $this->metadataProvider = $provider;

        return $this;
    }

    public function formatter(FormatsPhoneNumbers $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function normalizer(NormalizesPhoneNumbers $normalizer): static
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    public function validator(ValidatesPhoneNumbers $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function getMetadataProvider(): PhoneMetadataProvider
    {
        return $this->metadataProvider ??= app(PhoneMetadataProvider::class);
    }

    public function getFormatter(): FormatsPhoneNumbers
    {
        return $this->formatter ??= app()->bound(FormatsPhoneNumbers::class)
            ? app(FormatsPhoneNumbers::class)
            : new PhoneNumberFormatter($this->getMetadataProvider());
    }

    public function getNormalizer(): NormalizesPhoneNumbers
    {
        return $this->normalizer ??= app()->bound(NormalizesPhoneNumbers::class)
            ? app(NormalizesPhoneNumbers::class)
            : new PhoneNumberNormalizer($this->getFormatter());
    }

    public function getValidator(): ValidatesPhoneNumbers
    {
        return $this->validator ??= app(ValidatesPhoneNumbers::class);
    }
}
