<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns\HasAutocomplete;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns\HasAutoSubmit;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns\HasGrouping;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns\HasLength;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns\HasMode;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns\HasOtpAppearance;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns\HasPrivateMode;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns\HasSeparator;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Rendering\OtpViewModel;

/**
 * A one-time-code / PIN input rendered as a row of single-character cells,
 * as a first-class Filament form field.
 *
 * ```php
 * OtpInput::make('code')
 *     ->length(6);
 *
 * OtpInput::make('verification_code')
 *     ->length(6)
 *     ->numeric()
 *     ->autocomplete()   // browser one-time-code autofill
 *     ->autoSubmit()     // fire otp-completed when full
 *     ->group(3)         // 123 - 456
 *     ->separator('-');
 * ```
 *
 * ## A single scalar value
 *
 * Despite rendering as many cells, the field's state is one plain string —
 * the separator-free code (`"123456"`). It participates fully in the field
 * lifecycle: `live()`, `afterStateUpdated()`, `formatStateUsing()`,
 * hydration, and dehydration all behave exactly as they would on a
 * `TextInput`. The value is sanitized on the way in and out, so whatever the
 * browser sends, the stored code always matches the configured
 * {@see HasMode mode} and {@see HasLength length}.
 *
 * ## Behavior lives in the client
 *
 * Typing, backspace/arrow/Home/End/Delete navigation, selection replacement,
 * paste distribution (partial, complete, and overflow-protected), invalid
 * character rejection, whitespace trimming, masking, and auto-submit are all
 * handled in the entangled Alpine component, so no Livewire round-trip is
 * needed to move between cells — only the usual `live()` sync of the value
 * itself.
 *
 * ## Architecture
 *
 * Configuration is split across focused concerns — {@see HasLength},
 * {@see HasMode}, {@see HasGrouping}, {@see HasSeparator},
 * {@see HasPrivateMode}, {@see HasAutocomplete}, {@see HasAutoSubmit},
 * {@see HasOtpAppearance} — and resolved once into an immutable
 * {@see OtpViewModel} that both Blade and Alpine read.
 */
class OtpInput extends Field
{
    use CanBeReadOnly;
    use HasAutocomplete;
    use HasAutoSubmit;
    use HasExtraAlpineAttributes;
    use HasGrouping {
        group as configureGrouping;
    }
    use HasLength;
    use HasMode;
    use HasOtpAppearance;
    use HasPlaceholder;
    use HasPrivateMode;
    use HasSeparator {
        separator as configureSeparator;
    }

    protected string $view = 'filament-advanced-components::components.otp-input';

    /**
     * Grouping and separators imply each other: calling one applies the
     * other's default (a group of three, a `-` separator) *only* when the
     * other hasn't been configured, so `->group(3)` and `->separator('•')`
     * each stand alone while an explicit configuration is never overwritten.
     * Opt out of the auto-paired separator with `->separator(null)`.
     */
    public function group(int | array | Closure | null $size = 3): static
    {
        $this->configureGrouping($size);

        if (! $this->isSeparatorConfigured()) {
            $this->configureSeparator();
        }

        return $this;
    }

    public function separator(string | Closure | null $separator = '-'): static
    {
        $this->configureSeparator($separator);

        if (! $this->isGroupingConfigured()) {
            $this->configureGrouping();
        }

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Whatever the browser posts, the stored value is always a clean
        // code matching the mode and length; an empty code dehydrates to
        // null so "optional and blank" validates correctly.
        $this->dehydrateStateUsing(static function (OtpInput $component, mixed $state): ?string {
            $clean = $component->sanitize($state);

            return $clean === '' ? null : $clean;
        });

        // Coerce hydrated model state (ints, padded strings, …) into the
        // same clean shape the cells expect.
        $this->afterStateHydrated(static function (OtpInput $component, mixed $state): void {
            $component->state($component->sanitize($state));
        });

        // Exact length + character policy, enforced server-side and skipped
        // for an empty optional value (the `required` rule owns emptiness).
        $this->rule(static fn(OtpInput $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            if (blank($value)) {
                return;
            }

            $length = $component->getLength();
            $class = $component->getMode()->characterClass();

            if (! preg_match("/^[{$class}]{{$length}}$/u", (string) $value)) {
                $fail(__('filament-advanced-components::otp-input.validation.invalid', [
                    'attribute' => $component->getValidationAttribute(),
                    'length' => $length,
                ]));
            }
        });
    }

    /**
     * Strips whitespace and any character outside the current
     * {@see HasMode mode}, then truncates to the configured
     * {@see HasLength length}. The single choke point both dehydration and
     * hydration flow through.
     */
    public function sanitize(mixed $state): string
    {
        $string = is_scalar($state) ? (string) $state : '';

        $class = $this->getMode()->characterClass();

        $filtered = preg_replace("/[^{$class}]/u", '', $string) ?? '';

        return mb_substr($filtered, 0, $this->getLength());
    }

    public function getViewModel(): OtpViewModel
    {
        return new OtpViewModel(
            length: $this->getLength(),
            mode: $this->getMode(),
            isPrivate: $this->isPrivate(),
            maskCharacter: $this->getMaskCharacter(),
            isDisabled: $this->isDisabled(),
            isReadOnly: $this->isReadOnly(),
            isRequired: $this->isRequired(),
            isAutofocused: $this->isAutofocused(),
            hasAutocomplete: $this->hasAutocomplete(),
            shouldAutoSubmit: $this->shouldAutoSubmit(),
            autoSubmitAction: $this->getAutoSubmitAction(),
            groups: $this->getGroups(),
            separator: $this->getSeparator(),
            size: $this->getSize(),
            shape: $this->getShape(),
            cellWidth: $this->getCellWidth(),
            gap: $this->getGap(),
            placeholders: $this->getPlaceholders(),
        );
    }

    /**
     * The per-cell placeholder characters. A single-character placeholder
     * fills every cell; a full-length one maps character-for-character; any
     * other length repeats its first character.
     *
     * @return array<int, string>
     */
    public function getPlaceholders(): array
    {
        $length = $this->getLength();
        $placeholder = $this->getPlaceholder() ?? '';

        if ($placeholder === '') {
            return array_fill(0, $length, '');
        }

        $characters = mb_str_split($placeholder);

        if (count($characters) === 1) {
            return array_fill(0, $length, $characters[0]);
        }

        $placeholders = [];

        for ($i = 0; $i < $length; $i++) {
            $placeholders[] = $characters[$i] ?? $characters[0];
        }

        return $placeholders;
    }
}
