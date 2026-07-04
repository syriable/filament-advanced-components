<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support;

use Illuminate\Support\Str;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\MasksText;

/**
 * Default {@see MasksText} implementation.
 *
 * Multibyte-safe: offsets and lengths are counted in characters, not bytes,
 * so masking works correctly for accented and non-Latin text.
 */
class TextMasker implements MasksText
{
    public function mask(string $text, string $character = '•', int $index = 0, ?int $length = null): string
    {
        if ($text === '') {
            return $text;
        }

        $totalLength = mb_strlen($text);

        if ($index < 0) {
            $index = max($totalLength + $index, 0);
        }

        if ($index >= $totalLength) {
            return $text;
        }

        $maskLength = $length ?? ($totalLength - $index);
        $maskLength = min(max($maskLength, 0), $totalLength - $index);

        if ($maskLength === 0) {
            return $text;
        }

        return mb_substr($text, 0, $index)
            . str_repeat($character, $maskLength)
            . mb_substr($text, $index + $maskLength);
    }

    public function maskEmail(string $email, string $character = '•'): string
    {
        if (! str_contains($email, '@')) {
            return $this->mask($email, $character, 1);
        }

        $local = Str::beforeLast($email, '@');
        $domain = Str::afterLast($email, '@');

        $visible = mb_substr($local, 0, 1);
        $maskedLength = max(mb_strlen($local) - 1, 1);

        return $visible . str_repeat($character, $maskedLength) . '@' . $domain;
    }
}
