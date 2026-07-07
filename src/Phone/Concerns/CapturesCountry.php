<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\PhoneInput;

/**
 * Project the phone number's *own* country onto a sibling column, independent
 * of anything else the application knows about the user.
 *
 * A phone number carries its own country in the number itself — the field
 * already resolves it offline via libphonenumber on every parse. This concern
 * exposes that as a value worth *persisting*, for the common case where the
 * number's country is meaningfully different from (and more trustworthy than)
 * a user's profile country: a support agent logging a customer's alternate
 * number, a signup completed while travelling, a household's shared account.
 *
 * ```php
 * PhoneInput::make('phone')
 *     ->captureCountryTo('phone_country')      // ISO alpha-2, e.g. "SA" — always 2 letters
 *     ->captureDialCodeTo('phone_dial_code');  // E.164 calling code, e.g. 966 — 1 to 3 digits
 * ```
 *
 * Both are off by default — call either (or both) to opt in. Nothing needs to
 * exist in the schema for the target path: the value is injected directly into
 * the saved form state alongside every other field's own key (see
 * {@see PhoneInput::getStateToDehydrate()}),
 * so a plain fillable model column is enough to persist it — no hidden field,
 * no reactive wiring, no `->live()` required. The projection always reflects
 * the number as currently typed: `null` while the number is blank or
 * unparseable, the resolved value the moment it becomes a real number.
 */
trait CapturesCountry
{
    protected string | Closure | null $countryCaptureStatePath = null;

    protected string | Closure | null $dialCodeCaptureStatePath = null;

    /**
     * Capture the number's ISO 3166-1 alpha-2 country (`US`, `SA`, `GB`, …)
     * into another field's state path on save. Pass `null` to disable.
     *
     * The path is relative to this field's own container, exactly like a
     * sibling field's name — the same convention `$set()` uses — so it works
     * unmodified inside repeaters and nested groups.
     */
    public function captureCountryTo(string | Closure | null $statePath): static
    {
        $this->countryCaptureStatePath = $statePath;

        return $this;
    }

    /**
     * Capture the number's E.164 calling code (`1`, `44`, `966`, …) into
     * another field's state path on save. Pass `null` to disable.
     *
     * Calling codes are 1 to 3 digits and are *not* a reliable fixed-width
     * value — prefer {@see captureCountryTo()} as the stable identifier, and
     * reach for this only when the numeric code itself is what you need to
     * store (e.g. for a `tel:` link or a billing rule keyed by calling code).
     */
    public function captureDialCodeTo(string | Closure | null $statePath): static
    {
        $this->dialCodeCaptureStatePath = $statePath;

        return $this;
    }

    public function getCountryCaptureStatePath(): ?string
    {
        return $this->evaluate($this->countryCaptureStatePath);
    }

    public function getDialCodeCaptureStatePath(): ?string
    {
        return $this->evaluate($this->dialCodeCaptureStatePath);
    }

    public function capturesCountry(): bool
    {
        return filled($this->getCountryCaptureStatePath());
    }

    public function capturesDialCode(): bool
    {
        return filled($this->getDialCodeCaptureStatePath());
    }

    public function capturesAnything(): bool
    {
        return $this->capturesCountry() || $this->capturesDialCode();
    }

    /**
     * Resolve a relative sibling path (`'phone_country'`) to an absolute state
     * path, reusing this field's own container prefix — so a capture target
     * lands next to the phone field itself, however deeply the schema is
     * nested.
     */
    protected function resolveCaptureStatePath(string $relativePath): string
    {
        $containerPath = $this->getContainer()->getStatePath();

        return filled($containerPath) ? "{$containerPath}.{$relativePath}" : $relativePath;
    }
}
