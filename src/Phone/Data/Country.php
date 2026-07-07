<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Data;

/**
 * One country in the selector: everything the dropdown, the search index, and
 * the client-side dial-code composition need, resolved once and immutable.
 *
 * The flag is a pure emoji derived from the ISO code (see {@see flagFrom()}),
 * so the package ships no image assets and never makes a network request for a
 * sprite sheet — the two Regional Indicator Symbols that spell the ISO code
 * render as the flag on every modern platform.
 */
readonly class Country
{
    /**
     * @param  string  $iso  Upper-case ISO 3166-1 alpha-2 code, e.g. `GB`.
     * @param  string  $name  Localized display name, e.g. `United Kingdom`.
     * @param  int  $dialCode  The E.164 calling code, e.g. `44`.
     * @param  string  $flag  The emoji flag.
     * @param  bool  $preferred  Whether it is pinned to the top of the list.
     */
    public function __construct(
        public string $iso,
        public string $name,
        public int $dialCode,
        public string $flag,
        public bool $preferred = false,
    ) {}

    /**
     * Build a country, deriving the flag emoji from the ISO code.
     */
    public static function make(string $iso, string $name, int $dialCode, bool $preferred = false): self
    {
        $iso = strtoupper($iso);

        return new self(
            iso: $iso,
            name: $name,
            dialCode: $dialCode,
            flag: self::flagFrom($iso),
            preferred: $preferred,
        );
    }

    /**
     * The `+`-prefixed calling code, e.g. `+44`.
     */
    public function dialCodeLabel(): string
    {
        return '+' . $this->dialCode;
    }

    /**
     * A copy pinned (or unpinned) as preferred, without mutating the original.
     */
    public function withPreferred(bool $preferred = true): self
    {
        return new self($this->iso, $this->name, $this->dialCode, $this->flag, $preferred);
    }

    /**
     * The lowercase haystack the client searches: name, ISO code, and dial
     * code all match a single query.
     */
    public function searchHaystack(): string
    {
        return strtolower($this->name . ' ' . $this->iso . ' +' . $this->dialCode);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'iso' => $this->iso,
            'name' => $this->name,
            'dialCode' => $this->dialCode,
            'flag' => $this->flag,
            'preferred' => $this->preferred,
        ];
    }

    /**
     * The emoji flag for an ISO alpha-2 code, spelled with the two matching
     * Regional Indicator Symbols (U+1F1E6..U+1F1FF).
     */
    public static function flagFrom(string $iso): string
    {
        $iso = strtoupper($iso);

        if (! preg_match('/^[A-Z]{2}$/', $iso)) {
            return '';
        }

        $offset = 0x1F1E6 - ord('A');

        return mb_chr(ord($iso[0]) + $offset, 'UTF-8') . mb_chr(ord($iso[1]) + $offset, 'UTF-8');
    }
}
