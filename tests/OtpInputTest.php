<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\OtpInput;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpMode;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpShape;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpSize;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

beforeEach(function () {
    View::share('errors', new ViewErrorBag);
});

function makeOtp(string $name = 'code'): OtpInput
{
    return OtpInput::make($name)->container(Schema::make(new SchemaLivewireComponent));
}

function renderOtp(OtpInput $field): string
{
    return $field->toHtml();
}

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

it('defaults to a 6-cell numeric field', function () {
    $field = makeOtp();

    expect($field->getLength())->toBe(6)
        ->and($field->getMode())->toBe(OtpMode::Numeric)
        ->and($field->getMode()->characterClass())->toBe('0-9')
        ->and($field->getMode()->inputMode())->toBe('numeric');
});

it('evaluates length lazily', function () {
    expect(makeOtp()->length(fn (): int => 4)->getLength())->toBe(4)
        ->and(makeOtp()->length(0)->getLength())->toBe(1); // floored at 1
});

it('supports each character mode', function () {
    expect(makeOtp()->alphabetic()->getMode())->toBe(OtpMode::Alphabetic)
        ->and(makeOtp()->alphabetic()->getMode()->characterClass())->toBe('A-Za-z')
        ->and(makeOtp()->alphanumeric()->getMode()->characterClass())->toBe('A-Za-z0-9')
        ->and(makeOtp()->alphabetic()->getMode()->inputMode())->toBe('text')
        ->and(makeOtp()->numeric()->getMode())->toBe(OtpMode::Numeric);
});

// ---------------------------------------------------------------------------
// Grouping & separators
// ---------------------------------------------------------------------------

it('splits a length into even groups', function () {
    expect(makeOtp()->length(6)->group(3)->getGroups())->toBe([3, 3])
        ->and(makeOtp()->length(6)->group(2)->getGroups())->toBe([2, 2, 2])
        ->and(makeOtp()->length(7)->group(3)->getGroups())->toBe([3, 3, 1]) // remainder
        ->and(makeOtp()->length(6)->getGroups())->toBe([6]) // ungrouped
        ->and(makeOtp()->length(6)->group(3)->isGrouped())->toBeTrue()
        ->and(makeOtp()->length(6)->isGrouped())->toBeFalse();
});

it('accepts explicit uneven groups and never orphans a cell', function () {
    expect(makeOtp()->length(7)->group([2, 3, 2])->getGroups())->toBe([2, 3, 2])
        ->and(makeOtp()->length(6)->group([2, 2])->getGroups())->toBe([2, 2, 2]) // leftover joins
        ->and(makeOtp()->length(4)->group([9])->getGroups())->toBe([4]); // clamped
});

it('exposes a customizable separator', function () {
    expect(makeOtp()->separator('-')->getSeparator())->toBe('-')
        ->and(makeOtp()->separator(fn (): string => '•')->getSeparator())->toBe('•')
        ->and(makeOtp()->getSeparator())->toBeNull();
});

it('auto-pairs grouping and separators, without overwriting explicit values', function () {
    // group() alone applies the default separator.
    $grouped = makeOtp()->length(6)->group(3);
    expect($grouped->getGroups())->toBe([3, 3])
        ->and($grouped->getSeparator())->toBe('-');

    // separator() alone enables the default grouping.
    $separated = makeOtp()->length(6)->separator('•');
    expect($separated->isGrouped())->toBeTrue()
        ->and($separated->getSeparator())->toBe('•');

    // An explicit configuration on either side is never overwritten.
    $explicit = makeOtp()->length(6)->group(2)->separator('|');
    expect($explicit->getGroups())->toBe([2, 2, 2])
        ->and($explicit->getSeparator())->toBe('|');

    // Opt out of the auto-paired separator while keeping the grouping.
    $noSeparator = makeOtp()->length(6)->group(3)->separator(null);
    expect($noSeparator->isGrouped())->toBeTrue()
        ->and($noSeparator->getSeparator())->toBeNull();

    // Neither called: no grouping, no separator (unchanged default).
    expect(makeOtp()->length(6)->isGrouped())->toBeFalse()
        ->and(makeOtp()->length(6)->getSeparator())->toBeNull();
});

// ---------------------------------------------------------------------------
// Private, autocomplete, auto-submit, appearance
// ---------------------------------------------------------------------------

it('supports private mode with a customizable mask', function () {
    expect(makeOtp()->private()->isPrivate())->toBeTrue()
        ->and(makeOtp()->isPrivate())->toBeFalse()
        ->and(makeOtp()->private()->getMaskCharacter())->toBe('•')
        ->and(makeOtp()->maskCharacter('*')->getMaskCharacter())->toBe('*')
        ->and(makeOtp()->maskCharacter('')->getMaskCharacter())->toBe('•'); // never empty
});

it('toggles autocomplete', function () {
    expect(makeOtp()->autocomplete()->hasAutocomplete())->toBeTrue()
        ->and(makeOtp()->hasAutocomplete())->toBeFalse();
});

it('resolves auto-submit as a flag or a named action', function () {
    expect(makeOtp()->autoSubmit()->shouldAutoSubmit())->toBeTrue()
        ->and(makeOtp()->autoSubmit()->getAutoSubmitAction())->toBeNull()
        ->and(makeOtp()->autoSubmit('verify')->shouldAutoSubmit())->toBeTrue()
        ->and(makeOtp()->autoSubmit('verify')->getAutoSubmitAction())->toBe('verify')
        ->and(makeOtp()->shouldAutoSubmit())->toBeFalse()
        ->and(makeOtp()->autoSubmit(false)->shouldAutoSubmit())->toBeFalse();
});

it('exposes appearance knobs', function () {
    expect(makeOtp()->large()->getSize())->toBe(OtpSize::Large)
        ->and(makeOtp()->compact()->getSize())->toBe(OtpSize::Compact)
        ->and(makeOtp()->square()->getShape())->toBe(OtpShape::Square)
        ->and(makeOtp()->rounded()->getShape())->toBe(OtpShape::Rounded)
        ->and(makeOtp()->cellWidth(40)->getCellWidth())->toBe('40px')
        ->and(makeOtp()->cellGap('1rem')->getGap())->toBe('1rem');
});

// ---------------------------------------------------------------------------
// State: sanitization, hydration, dehydration
// ---------------------------------------------------------------------------

it('sanitizes to the mode and length', function () {
    expect(makeOtp()->length(6)->sanitize(' 12 34 56 '))->toBe('123456') // trims + strips spaces
        ->and(makeOtp()->length(4)->sanitize('123456'))->toBe('1234') // capped
        ->and(makeOtp()->length(6)->sanitize('12ab34'))->toBe('1234') // strips letters in numeric
        ->and(makeOtp()->length(6)->alphanumeric()->sanitize('12ab-34'))->toBe('12ab34')
        ->and(makeOtp()->length(6)->sanitize(null))->toBe('')
        ->and(makeOtp()->length(6)->sanitize(123456))->toBe('123456'); // integer model value
});

it('dehydrates a clean value, and null when empty', function () {
    $field = makeOtp()->length(6);
    $path = $field->getStatePath();

    expect($field->getStateToDehydrate('1234x9'))->toBe([$path => '12349']) // strips invalid
        ->and($field->getStateToDehydrate('123456'))->toBe([$path => '123456'])
        ->and($field->getStateToDehydrate('   '))->toBe([$path => null]); // whitespace → empty → null
});

it('sanitizes hydrated model state', function () {
    $field = makeOtp()->length(6);

    // A typed / unpadded model value is coerced to a clean string on hydration.
    $field->state(1234);
    $field->callAfterStateHydrated();

    expect($field->getState())->toBe('1234');
});

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

function otpValidator(OtpInput $field): Closure
{
    foreach ($field->getValidationRules() as $rule) {
        if ($rule instanceof Closure) {
            return $rule;
        }
    }

    throw new RuntimeException('No closure validation rule found.');
}

it('fails validation for the wrong length or characters', function () {
    $field = makeOtp()->length(6);
    $validator = otpValidator($field);

    $failures = [];
    $fail = function (string $message) use (&$failures): void {
        $failures[] = $message;
    };

    $validator('code', '12345', $fail);   // too short
    $validator('code', '1234567', $fail); // too long
    $validator('code', '12345a', $fail);  // non-numeric

    expect($failures)->toHaveCount(3);
});

it('passes validation for a correct code and skips empty values', function () {
    $field = makeOtp()->length(6);
    $validator = otpValidator($field);

    $failed = false;
    $fail = function () use (&$failed): void {
        $failed = true;
    };

    $validator('code', '123456', $fail);
    $validator('code', '', $fail); // optional + empty: required rule owns this

    expect($failed)->toBeFalse();
});

// ---------------------------------------------------------------------------
// View model
// ---------------------------------------------------------------------------

it('builds a render-ready view model', function () {
    $vm = makeOtp()
        ->length(6)
        ->group(3)
        ->separator('-')
        ->autoSubmit('verify')
        ->getViewModel();

    expect($vm->alpineConfig())->toMatchArray([
        'length' => 6,
        'mode' => 'numeric',
        'characterClass' => '0-9',
        'shouldAutoSubmit' => true,
        'autoSubmitAction' => 'verify',
    ])
        ->and($vm->groupedIndexes())->toBe([[0, 1, 2], [3, 4, 5]])
        ->and($vm->isGrouped())->toBeTrue();
});

it('maps placeholders across cells', function () {
    expect(makeOtp()->length(4)->placeholder('•')->getPlaceholders())->toBe(['•', '•', '•', '•'])
        ->and(makeOtp()->length(4)->placeholder('1234')->getPlaceholders())->toBe(['1', '2', '3', '4'])
        ->and(makeOtp()->length(4)->getPlaceholders())->toBe(['', '', '', '']);
});

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

it('renders the cells inside the field wrapper', function () {
    $html = renderOtp(makeOtp('otp')->length(4));

    expect($html)->toContain('fi-otp-input')
        ->and(substr_count($html, 'data-otp-cell'))->toBe(4)
        ->and($html)->toContain('inputmode="numeric"')
        ->and($html)->toContain('maxlength="1"')
        ->and($html)->toContain('role="group"')
        ->and($html)->toContain('otpInput({')
        ->and($html)->toContain('x-load-src');
});

it('renders one-time-code autofill only on the first cell', function () {
    $html = renderOtp(makeOtp('otp')->length(4)->autocomplete());

    expect(substr_count($html, 'autocomplete="one-time-code"'))->toBe(1)
        ->and(substr_count($html, 'autocomplete="off"'))->toBe(3);
});

it('renders separators between groups', function () {
    $html = renderOtp(makeOtp('otp')->length(6)->group(3)->separator('-'));

    expect($html)->toContain('fi-otp-input-separator')
        ->and(substr_count($html, 'fi-otp-input-group'))->toBe(2);
});

it('renders the auto-paired separator when only group() is set', function () {
    $html = renderOtp(makeOtp('otp')->length(6)->group(3));

    expect($html)->toContain('fi-otp-input-separator')
        ->and(substr_count($html, 'fi-otp-input-group'))->toBe(2);
});

it('passes each cell index to the focus handler for click redirection', function () {
    $html = renderOtp(makeOtp('otp')->length(3));

    expect($html)->toContain('onFocus($event, 0)')
        ->and($html)->toContain('onFocus($event, 1)')
        ->and($html)->toContain('onFocus($event, 2)');
});

it('renders size, shape, and private classes', function () {
    $html = renderOtp(makeOtp('otp')->large()->square()->private());

    expect($html)->toContain('fi-otp-input-size-lg')
        ->and($html)->toContain('fi-otp-input-shape-square')
        ->and($html)->toContain('fi-otp-input-private');
});

it('supports alignment within the field container', function () {
    expect(makeOtp()->alignment('center')->getAlignmentClass())->toBe('fi-otp-input-align-center')
        ->and(makeOtp()->alignEnd()->getAlignmentClass())->toBe('fi-otp-input-align-end')
        ->and(makeOtp()->getAlignmentClass())->toBeNull();

    $html = renderOtp(makeOtp('otp')->length(4)->alignment('center'));

    expect($html)->toContain('fi-otp-input-align-center');
});

it('renders per-cell accessible labels', function () {
    $html = renderOtp(makeOtp('otp')->length(3));

    expect($html)->toContain('Character 1 of 3')
        ->and($html)->toContain('Character 3 of 3');
});
