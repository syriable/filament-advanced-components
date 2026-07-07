<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Rendering;

use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\PhoneInput;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\Country;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;

/**
 * The fully-resolved, render-ready snapshot of a
 * {@see PhoneInput}.
 *
 * Every lazy value, closure, and derived list has been evaluated by the time
 * this object exists, so both the Blade template and the Alpine layer read a
 * static description and never re-run field logic. {@see alpineConfig()} is the
 * single JSON payload handed to the client (the live value is entangled
 * separately in Blade so it stays reactive).
 */
readonly class PhoneViewModel
{
    /**
     * @param  array<int, Country>  $countries  The available, ordered catalog.
     * @param  array<string, string>  $examples  ISO → national example number.
     */
    public function __construct(
        public PhoneNumberData $number,
        public ?Country $selectedCountry,
        public array $countries,
        public array $examples,
        public string $displayValue,
        public string $nationalValue,
        public ?string $placeholder,
        public bool $placeholderFromCountry,
        public PhoneFormat $storageFormat,
        public PhoneFormat $displayFormat,
        public bool $hasCountrySelector,
        public bool $showFlags,
        public bool $showDialCode,
        public bool $searchEnabled,
        public int $searchDebounce,
        public bool $hasExtension,
        public int $maxExtensionLength,
        public bool $showExample,
        public bool $showType,
        public bool $copyable,
        public bool $clearable,
        public bool $isDisabled,
        public bool $isReadOnly,
        public bool $isRequired,
        public bool $isAutofocused,
    ) {}

    /**
     * The client configuration: everything except the entangled live value.
     * Countries are flattened to plain arrays so the payload is pure data.
     *
     * @return array<string, mixed>
     */
    public function alpineConfig(): array
    {
        return [
            'countries' => array_map(static fn (Country $c): array => $c->toArray(), $this->countries),
            'examples' => $this->examples,
            'initialCountry' => $this->selectedCountry?->iso,
            'hasCountrySelector' => $this->hasCountrySelector,
            'showFlags' => $this->showFlags,
            'showDialCode' => $this->showDialCode,
            'searchEnabled' => $this->searchEnabled,
            'searchDebounce' => $this->searchDebounce,
            'hasExtension' => $this->hasExtension,
            'maxExtensionLength' => $this->maxExtensionLength,
            'showExample' => $this->showExample,
            'showType' => $this->showType,
            'placeholderFromCountry' => $this->placeholderFromCountry,
            'isDisabled' => $this->isDisabled,
            'isReadOnly' => $this->isReadOnly,
        ];
    }

    /**
     * The example number for the currently selected country, if any — the
     * initial placeholder/hint before the client takes over.
     */
    public function selectedExample(): ?string
    {
        $iso = $this->selectedCountry?->iso;

        return $iso === null ? null : ($this->examples[$iso] ?? null);
    }

    /**
     * The placeholder to render initially: an explicit one wins, otherwise the
     * selected country's example number when country placeholders are enabled.
     */
    public function resolvedPlaceholder(): ?string
    {
        if ($this->placeholder !== null) {
            return $this->placeholder;
        }

        return $this->placeholderFromCountry ? $this->selectedExample() : null;
    }
}
