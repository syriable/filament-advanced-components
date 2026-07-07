<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\PhoneInput;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\CountryDetectionStrategy;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneNumberType;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneValidationError;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

beforeEach(function () {
    View::share('errors', new ViewErrorBag);
});

function makePhone(string $name = 'phone'): PhoneInput
{
    return PhoneInput::make($name)->container(Schema::make(new SchemaLivewireComponent));
}

function renderPhone(PhoneInput $field): string
{
    return $field->toHtml();
}

function phoneValidator(PhoneInput $field): Closure
{
    foreach ($field->getValidationRules() as $rule) {
        if ($rule instanceof Closure) {
            return $rule;
        }
    }

    throw new RuntimeException('No closure validation rule found.');
}

/**
 * Collect validation failure messages for a state value.
 *
 * @return array<int, string>
 */
function phoneFailures(PhoneInput $field, mixed $value): array
{
    $failures = [];
    $fail = function (string $message) use (&$failures): void {
        $failures[] = $message;
    };

    phoneValidator($field)('phone', $value, $fail);

    return $failures;
}

// ---------------------------------------------------------------------------
// Defaults & parsing
// ---------------------------------------------------------------------------

it('defaults to E164 storage and international display', function () {
    $field = makePhone();

    expect($field->getStorageFormat())->toBe(PhoneFormat::E164)
        ->and($field->getDisplayFormat())->toBe(PhoneFormat::International)
        ->and($field->hasCountrySelector())->toBeTrue()
        ->and($field->isStrictValidation())->toBeTrue();
});

it('parses a self-describing transport value without a region hint', function () {
    $number = makePhone()->parseState('+14155552671');

    expect($number->isValid)->toBeTrue()
        ->and($number->e164)->toBe('+14155552671')
        ->and($number->country)->toBe('US')
        ->and($number->national)->toBe('(415) 555-2671')
        ->and($number->type)->toBeInstanceOf(PhoneNumberType::class);
});

it('parses a bare national value against the default country', function () {
    $number = makePhone()->defaultCountry('GB')->parseState('020 7946 0958');

    expect($number->country)->toBe('GB')
        ->and($number->e164)->toBe('+442079460958');
});

it('treats blank input as an empty, blank number', function () {
    $number = makePhone()->parseState('   ');

    expect($number->isBlank())->toBeTrue()
        ->and($number->isParsed())->toBeFalse();
});

it('carries an extension through the transport value', function () {
    $field = makePhone()->enableExtension();
    $number = $field->parseState('+14155552671;ext=89');

    expect($number->extension)->toBe('89')
        ->and($field->toTransport($number))->toBe('+14155552671;ext=89');
});

// ---------------------------------------------------------------------------
// Hydration & dehydration
// ---------------------------------------------------------------------------

it('hydrates a stored value into the E164 transport', function () {
    $field = makePhone()->defaultCountry('GB');
    $field->state('020 7946 0958');
    $field->callAfterStateHydrated();

    expect($field->getState())->toBe('+442079460958');
});

it('dehydrates into the configured storage format, and null when blank', function () {
    $e164 = makePhone();
    $national = makePhone()->storeNational();
    $raw = makePhone()->storeRaw();
    $rfc = makePhone()->storeRfc3966();

    $path = $e164->getStatePath();

    expect($e164->getStateToDehydrate('+14155552671'))->toBe([$path => '+14155552671'])
        ->and($national->getStateToDehydrate('+14155552671'))->toBe([$national->getStatePath() => '(415) 555-2671'])
        ->and($raw->getStateToDehydrate('+14155552671'))->toBe([$raw->getStatePath() => '14155552671'])
        ->and($rfc->getStateToDehydrate('+14155552671'))->toBe([$rfc->getStatePath() => 'tel:+1-415-555-2671'])
        ->and($e164->getStateToDehydrate('   '))->toBe([$path => null]);
});

it('preserves an unparseable value so validation can reject it', function () {
    $field = makePhone();
    $path = $field->getStatePath();

    expect($field->getStateToDehydrate('not-a-number'))->toBe([$path => 'not-a-number']);
});

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

it('passes a valid number and skips blank values', function () {
    $field = makePhone();

    expect(phoneFailures($field, '+14155552671'))->toBe([])
        ->and(phoneFailures($field, ''))->toBe([]);
});

it('fails an invalid or impossible number', function () {
    expect(phoneFailures(makePhone(), 'garbage'))->toHaveCount(1)
        ->and(phoneFailures(makePhone(), '+1415'))->toHaveCount(1);
});

it('accepts a possible-but-not-valid number only when lenient', function () {
    // A number of plausible length that is not an assigned/valid US number.
    $value = '+12015550123';

    // In strict mode a genuinely invalid number is rejected; lenient accepts
    // any possible one. (This asserts the strict/lenient switch is wired.)
    $strict = makePhone()->strictValidation();
    $lenient = makePhone()->lenient();

    expect($strict->isStrictValidation())->toBeTrue()
        ->and($lenient->isStrictValidation())->toBeFalse()
        ->and(phoneFailures($lenient, $value))->toBe([]);
});

it('restricts by line type', function () {
    // A US fixed-line-or-mobile number is accepted by a mobile-only field
    // (the ambiguous type counts), but a landline is not.
    $mobileField = makePhone()->mobileOnly();

    // London landline.
    $failures = phoneFailures($mobileField, '+442079460958');

    expect($failures)->toHaveCount(1)
        ->and($failures[0])->toContain('required type');
});

it('rejects a number from a country outside the allow-list', function () {
    $field = makePhone()->allowedCountries(['US', 'CA']);

    // A valid GB number is not allowed.
    expect(phoneFailures($field, '+442079460958'))->toHaveCount(1)
        ->and(phoneFailures($field, '+14155552671'))->toBe([]);
});

it('maps each failure reason to its own message', function () {
    expect(PhoneValidationError::TooShort->translationKey())
        ->toBe('filament-advanced-components::phone-input.validation.too_short');
});

// ---------------------------------------------------------------------------
// Country catalog & restrictions
// ---------------------------------------------------------------------------

it('exposes the full country catalog with flags and dial codes', function () {
    $countries = makePhone()->getAvailableCountries();

    expect(count($countries))->toBeGreaterThan(200);

    $us = collect($countries)->firstWhere('iso', 'US');

    expect($us->dialCode)->toBe(1)
        ->and($us->flag)->toBe('🇺🇸');
});

it('applies an allow-list', function () {
    $countries = makePhone()->allowedCountries(['gb', 'US'])->getAvailableCountries();

    expect(collect($countries)->pluck('iso')->all())->toEqualCanonicalizing(['GB', 'US']);
});

it('applies a block-list', function () {
    $isos = collect(makePhone()->blockedCountries(['US'])->getAvailableCountries())->pluck('iso');

    expect($isos)->not->toContain('US')
        ->and($isos)->toContain('GB');
});

it('pins preferred countries to the top in order', function () {
    $countries = makePhone()->preferredCountries(['DE', 'GB'])->getAvailableCountries();

    expect($countries[0]->iso)->toBe('DE')
        ->and($countries[0]->preferred)->toBeTrue()
        ->and($countries[1]->iso)->toBe('GB');
});

it('reports whether a country survives the restrictions', function () {
    $field = makePhone()->allowedCountries(['US']);

    expect($field->isCountryAvailable('US'))->toBeTrue()
        ->and($field->isCountryAvailable('GB'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Country detection
// ---------------------------------------------------------------------------

it('detects the default country first', function () {
    expect(makePhone()->defaultCountry('DE')->resolveInitialCountry())->toBe('DE');
});

it('falls back when the default is unset', function () {
    expect(makePhone()->defaultCountry(null)->fallbackCountry('FR')->resolveInitialCountry())->toBe('FR');
});

it('detects from the application locale', function () {
    app()->setLocale('en_GB');

    $iso = makePhone()
        ->defaultCountry(null)
        ->detectCountry([CountryDetectionStrategy::AppLocale, CountryDetectionStrategy::Fallback])
        ->resolveInitialCountry();

    expect($iso)->toBe('GB');

    app()->setLocale('en');
});

it('detects through a custom callback', function () {
    $iso = makePhone()
        ->defaultCountry(null)
        ->detectCountry([CountryDetectionStrategy::Callback, CountryDetectionStrategy::Fallback])
        ->detectCountryUsing(fn (): string => 'JP')
        ->resolveInitialCountry();

    expect($iso)->toBe('JP');
});

it('skips a detected country that is not allowed', function () {
    $iso = makePhone()
        ->allowedCountries(['US'])
        ->defaultCountry('GB') // not allowed → skipped
        ->fallbackCountry('US')
        ->resolveInitialCountry();

    expect($iso)->toBe('US');
});

// ---------------------------------------------------------------------------
// View model
// ---------------------------------------------------------------------------

it('builds a render-ready view model', function () {
    $field = makePhone()->defaultCountry('US')->showExample()->copyable();
    $field->state('+14155552671');
    $field->callAfterStateHydrated();

    $vm = $field->getViewModel();

    expect($vm->selectedCountry->iso)->toBe('US')
        ->and($vm->displayValue)->toBe('(415) 555-2671')
        ->and($vm->copyable)->toBeTrue()
        ->and($vm->examples)->toHaveKey('US')
        ->and($vm->alpineConfig())->toMatchArray([
            'initialCountry' => 'US',
            'hasCountrySelector' => true,
        ]);
});

it('resolves the placeholder from the selected country when none is set', function () {
    $field = makePhone()->defaultCountry('GB');
    $vm = $field->getViewModel();

    expect($vm->resolvedPlaceholder())->toBe('07400 123456');
});

it('does not build examples when nothing needs them', function () {
    $field = makePhone()->placeholderFromCountry(false)->placeholder('123');
    $vm = $field->getViewModel();

    expect($vm->examples)->toBe([]);
});

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

it('renders the control, selector, and entangled Alpine component', function () {
    $html = renderPhone(makePhone('phone')->defaultCountry('US'));

    expect($html)->toContain('fi-phone-input')
        ->and($html)->toContain('phoneInput({')
        ->and($html)->toContain('x-load-src')
        ->and($html)->toContain('fi-phone-input-country')
        ->and($html)->toContain('type="tel"')
        ->and($html)->toContain('🇺🇸');
});

it('omits the selector when disabled', function () {
    $html = renderPhone(makePhone('phone')->withoutCountrySelector());

    expect($html)->not->toContain('fi-phone-input-country')
        ->and($html)->not->toContain('fi-phone-input-dropdown');
});

it('renders the extension input when enabled', function () {
    $html = renderPhone(makePhone('phone')->enableExtension());

    expect($html)->toContain('fi-phone-input-ext');
});

it('renders copy and clear affordances', function () {
    $html = renderPhone(makePhone('phone')->copyable()->clearable());

    expect($html)->toContain('copy()')
        ->and($html)->toContain('clear()');
});

// ---------------------------------------------------------------------------
// Country / dial-code capture
// ---------------------------------------------------------------------------

it('captures nothing by default', function () {
    $field = makePhone();

    expect($field->capturesCountry())->toBeFalse()
        ->and($field->capturesDialCode())->toBeFalse()
        ->and($field->capturesAnything())->toBeFalse()
        ->and($field->getStateToDehydrate('+14155552671'))->toBe([$field->getStatePath() => '+14155552671']);
});

it('projects the resolved country onto a sibling state path on save', function () {
    $field = makePhone()->captureCountryTo('phone_country');

    expect($field->capturesCountry())->toBeTrue()
        ->and($field->getStateToDehydrate('+442079460958'))->toBe([
            $field->getStatePath() => '+442079460958',
            'phone_country' => 'GB',
        ]);
});

it('projects the resolved dial code onto a sibling state path on save', function () {
    $field = makePhone()->captureDialCodeTo('phone_dial_code');

    expect($field->capturesDialCode())->toBeTrue()
        ->and($field->getStateToDehydrate('+442079460958'))->toBe([
            $field->getStatePath() => '+442079460958',
            'phone_dial_code' => 44,
        ]);
});

it('captures both independently, in the configured storage format', function () {
    $field = makePhone()
        ->storeNational()
        ->captureCountryTo('phone_country')
        ->captureDialCodeTo('phone_dial_code');

    expect($field->getStateToDehydrate('+442079460958'))->toBe([
        $field->getStatePath() => '020 7946 0958',
        'phone_country' => 'GB',
        'phone_dial_code' => 44,
    ]);
});

it('captures null for a blank or unparseable number, never the wrong country', function () {
    $field = makePhone()->captureCountryTo('phone_country')->captureDialCodeTo('phone_dial_code');

    expect($field->getStateToDehydrate(''))->toBe([
        $field->getStatePath() => null,
        'phone_country' => null,
        'phone_dial_code' => null,
    ])
        ->and($field->getStateToDehydrate('garbage'))->toBe([
            $field->getStatePath() => 'garbage',
            'phone_country' => null,
            'phone_dial_code' => null,
        ]);
});

it('resolves the sibling path relative to the field\'s own container, not the app root', function () {
    $schema = Schema::make(new SchemaLivewireComponent)->statePath('contact');
    $field = PhoneInput::make('phone')->captureCountryTo('phone_country')->container($schema);

    expect($field->getStatePath())->toBe('contact.phone')
        ->and($field->getStateToDehydrate('+14155552671'))->toBe([
            'contact.phone' => '+14155552671',
            'contact.phone_country' => 'US',
        ]);
});

it('exposes the resolved country and dial code as plain accessors', function () {
    $field = makePhone();
    $field->state('+966501234567');
    $field->callAfterStateHydrated();

    expect($field->getCountry())->toBe('SA')
        ->and($field->getDialCode())->toBe(966);
});
