<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\CapturesCountry;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\DetectsCountry;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\HasCountries;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\HasCountrySelector;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\HasPhoneAppearance;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\HasPhoneExtension;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\HasPhoneFormatting;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\HasPhoneValidation;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns\InteractsWithPhoneServices;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts\NormalizesPhoneNumbers;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\Country;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneNumberType;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Rendering\PhoneViewModel;

/**
 * An international phone-number field for Filament, backed by libphonenumber and
 * behaving exactly like a native field.
 *
 * ```php
 * PhoneInput::make('phone')
 *     ->defaultCountry('GB')
 *     ->preferredCountries(['GB', 'US', 'DE'])
 *     ->showExample()
 *     ->copyable();
 * ```
 *
 * ## One clean scalar, PHP as the source of truth
 *
 * However rich the widget looks, the field's state is a single string. The
 * client composes a self-describing E.164 transport value (`+<code><digits>`,
 * plus an optional `;ext=`), and the server does everything that matters with
 * it: {@see parseState()} turns it into an immutable {@see PhoneNumberData} via
 * libphonenumber, validation runs against that, and dehydration
 * {@see NormalizesPhoneNumbers normalizes}
 * it into the configured storage format (E.164 by default). Nothing the client
 * asserts is trusted — the number is parsed, validated, and formatted again
 * here.
 *
 * Because the transport is self-describing, the country is embedded in the
 * value; the selector is a convenience, not a second piece of state to keep in
 * sync.
 *
 * ## Everything is offline
 *
 * All metadata — the country catalog, calling codes, example numbers, validity,
 * and line types — comes from libphonenumber's bundled dataset. The field never
 * makes a network request to validate or format a number.
 *
 * ## Architecture
 *
 * Configuration is split across focused concerns — {@see HasCountries},
 * {@see DetectsCountry}, {@see HasPhoneFormatting}, {@see HasPhoneValidation},
 * {@see HasCountrySelector}, {@see HasPhoneExtension}, {@see HasPhoneAppearance},
 * {@see CapturesCountry}, {@see InteractsWithPhoneServices} — and the four
 * collaborators (metadata provider, formatter, normalizer, validator) are each
 * replaceable per field or globally. A single {@see PhoneViewModel} is
 * resolved once per render for both Blade and Alpine.
 */
class PhoneInput extends Field
{
    use CanBeReadOnly;
    use CapturesCountry;
    use DetectsCountry;
    use HasCountries;
    use HasCountrySelector;
    use HasExtraAlpineAttributes;
    use HasPhoneAppearance;
    use HasPhoneExtension;
    use HasPhoneFormatting;
    use HasPhoneValidation;
    use HasPlaceholder;
    use InteractsWithPhoneServices;

    protected string $view = 'filament-advanced-components::components.phone-input';

    protected function setUp(): void
    {
        parent::setUp();

        // Model value (storage format) → in-memory transport (self-describing
        // E.164 the client edits). Idempotent, so it is safe to re-run on every
        // hydration.
        $this->afterStateHydrated(static function (PhoneInput $component, mixed $state): void {
            $component->state($component->toTransport($component->parseState($state)));
        });

        // In-memory transport → the value actually stored, in the configured
        // storage format. A blank field stores null.
        $this->dehydrateStateUsing(static function (PhoneInput $component, mixed $state): ?string {
            $number = $component->parseState($state);

            return $component->getNormalizer()->normalize($number, $component->getStorageFormat());
        });

        // Server-side authority: re-parse and re-validate whatever the client
        // posted. An empty optional value is the `required` rule's concern.
        $this->rule(static function (PhoneInput $component): Closure {
            return static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                $number = $component->parseState($value);

                $result = $component->getValidator()->validate(
                    number: $number,
                    strict: $component->isStrictValidation(),
                    allowedTypes: $component->getAllowedTypes(),
                    allowedCountries: $component->getRestrictedCountryIsos(),
                );

                if ($result->fails()) {
                    $fail(__($result->error->translationKey(), [
                        'attribute' => $component->getValidationAttribute(),
                    ]));
                }
            };
        });
    }

    /**
     * Parse a state value into an immutable {@see PhoneNumberData}. A
     * self-describing value (containing `+`) is parsed as-is; a bare national
     * value is parsed against the field's default country, so national-format
     * storage still round-trips. Never throws.
     */
    public function parseState(mixed $state): PhoneNumberData
    {
        $value = is_scalar($state) ? trim((string) $state) : '';

        if ($value === '') {
            return PhoneNumberData::empty();
        }

        $region = str_contains($value, '+') ? null : $this->getParseRegion();

        return $this->getMetadataProvider()->parse($value, $region);
    }

    /**
     * The E.164 transport string for a parsed number — the value the client
     * edits — carrying the extension as an RFC 3966 `;ext=` suffix. Falls back
     * to the raw input when the number could not be parsed, so nothing the user
     * typed is silently dropped.
     */
    public function toTransport(PhoneNumberData $number): string
    {
        if (! $number->isParsed()) {
            return $number->raw;
        }

        $transport = (string) $number->e164;

        if ($number->extension !== null) {
            $transport .= ';ext=' . $number->extension;
        }

        return $transport;
    }

    /**
     * The region hint used when parsing a bare national value on hydration.
     */
    public function getParseRegion(): ?string
    {
        return $this->getDefaultCountry() ?? $this->getFallbackCountry();
    }

    /**
     * The ISO codes a submitted number must fall within, or an empty array when
     * the field places no country restriction — so the validator skips the
     * check entirely in the common, unrestricted case.
     *
     * @return array<int, string>
     */
    public function getRestrictedCountryIsos(): array
    {
        if ($this->getAllowedCountries() === [] && $this->getBlockedCountries() === []) {
            return [];
        }

        return array_map(static fn (Country $c): string => $c->iso, $this->getAvailableCountries());
    }

    /**
     * The parsed representation of the current state.
     */
    public function getPhoneNumber(): PhoneNumberData
    {
        return $this->parseState($this->getState());
    }

    /**
     * The number's own ISO 3166-1 alpha-2 country, resolved from the number
     * itself — never from the application locale, the authenticated user, or
     * any other ambient setting. `null` while the number is blank or
     * unparseable. A thin, discoverable wrapper over {@see getPhoneNumber()}
     * for use in callbacks (`afterStateUpdated()`, `mutateFormDataBeforeSave`,
     * a validation rule, …) that shouldn't need to know about
     * {@see PhoneNumberData}.
     */
    public function getCountry(): ?string
    {
        return $this->getPhoneNumber()->country;
    }

    /**
     * The number's own E.164 calling code (1 to 3 digits — never a fixed
     * width, and not unique to one country: see {@see CapturesCountry}).
     * `null` while the number is blank or unparseable.
     */
    public function getDialCode(): ?int
    {
        return $this->getPhoneNumber()->dialCode;
    }

    /**
     * Beyond the field's own key, project the number's country and/or dial
     * code onto whatever sibling paths {@see CapturesCountry} configured —
     * both are no-ops when their capture is disabled (the default). Nothing
     * needs to exist in the schema for those paths: Filament merges every
     * component's {@see getStateToDehydrate()} entries into one flat array
     * before a save, so a plain fillable model column is enough to receive
     * the projected value.
     *
     * @return array<string, mixed>
     */
    public function getStateToDehydrate(mixed $state): array
    {
        $dehydrated = parent::getStateToDehydrate($state);

        if (! $this->capturesAnything()) {
            return $dehydrated;
        }

        $number = $this->parseState($state);

        if ($path = $this->getCountryCaptureStatePath()) {
            $dehydrated[$this->resolveCaptureStatePath($path)] = $number->country;
        }

        if ($path = $this->getDialCodeCaptureStatePath()) {
            $dehydrated[$this->resolveCaptureStatePath($path)] = $number->dialCode;
        }

        return $dehydrated;
    }

    /**
     * The complete, resolved snapshot the Blade view and Alpine layer render
     * from. All lazy configuration is evaluated exactly once here.
     */
    public function getViewModel(): PhoneViewModel
    {
        $number = $this->getPhoneNumber();
        $countries = $this->getAvailableCountries();
        $selectedCountry = $this->resolveSelectedCountry($number, $countries);
        $explicitPlaceholder = $this->getPlaceholder();

        return new PhoneViewModel(
            number: $number,
            selectedCountry: $selectedCountry,
            countries: $countries,
            examples: $this->buildExamples($countries, $explicitPlaceholder),
            displayValue: $this->resolveDisplayValue($number),
            nationalValue: $number->isParsed() ? (string) $number->national : $number->raw,
            placeholder: $explicitPlaceholder !== null ? (string) $explicitPlaceholder : null,
            placeholderFromCountry: $this->shouldUseCountryPlaceholder(),
            storageFormat: $this->getStorageFormat(),
            displayFormat: $this->getDisplayFormat(),
            hasCountrySelector: $this->hasCountrySelector(),
            showFlags: $this->shouldShowFlags(),
            showDialCode: $this->shouldShowDialCode(),
            searchEnabled: $this->isSearchEnabled(),
            searchDebounce: $this->getSearchDebounce(),
            hasExtension: $this->hasExtension(),
            maxExtensionLength: $this->getMaxExtensionLength(),
            showExample: $this->shouldShowExample(),
            showType: $this->shouldShowType(),
            copyable: $this->isCopyable(),
            clearable: $this->isClearable(),
            isDisabled: $this->isDisabled(),
            isReadOnly: $this->isReadOnly(),
            isRequired: $this->isRequired(),
            isAutofocused: $this->isAutofocused(),
        );
    }

    /**
     * The country the widget starts on: the number's own country when it has
     * one, otherwise the detected initial country. Always returned from the
     * available list so the selector can show it.
     *
     * @param  array<int, Country>  $countries
     */
    protected function resolveSelectedCountry(PhoneNumberData $number, array $countries): ?Country
    {
        $iso = $number->country ?? $this->resolveInitialCountry();

        if ($iso === null) {
            return $countries[0] ?? null;
        }

        foreach ($countries as $country) {
            if ($country->iso === $iso) {
                return $country;
            }
        }

        // The number's country is outside the available list (e.g. a blocked
        // country pasted in); still surface it so the value renders correctly.
        return $this->getMetadataProvider()->country($iso, $this->getCountryLocale())
            ?? ($countries[0] ?? null);
    }

    /**
     * The string shown in the visible input initially: the national number for
     * an editable field (the country lives in the selector), or the full
     * display-format string when the field is read-only.
     */
    protected function resolveDisplayValue(PhoneNumberData $number): string
    {
        if (! $number->isParsed()) {
            return $number->raw;
        }

        if ($this->isDisabled() || $this->isReadOnly()) {
            return $this->getFormatter()->format($number, $this->getDisplayFormat(), $number->raw) ?? '';
        }

        return (string) $number->national;
    }

    /**
     * The ISO → national-example map handed to the client for reactive
     * placeholders and the "e.g. …" hint. Built only when something actually
     * needs it, and memoized in the provider so repeated renders are cheap.
     *
     * @param  array<int, Country>  $countries
     * @return array<string, string>
     */
    protected function buildExamples(array $countries, mixed $explicitPlaceholder): array
    {
        $needsExamples = $this->shouldShowExample()
            || ($this->shouldUseCountryPlaceholder() && $explicitPlaceholder === null);

        if (! $needsExamples) {
            return [];
        }

        $provider = $this->getMetadataProvider();
        $examples = [];

        foreach ($countries as $country) {
            $example = $provider->exampleNumber($country->iso, PhoneNumberType::Mobile);

            if ($example !== null) {
                $examples[$country->iso] = $example;
            }
        }

        return $examples;
    }
}
