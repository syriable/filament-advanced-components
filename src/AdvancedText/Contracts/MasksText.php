<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts;

use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\TextMasker;

/**
 * Masks sensitive text before it is rendered.
 *
 * The default implementation is
 * {@see TextMasker}.
 * Rebind this contract in the container to change how every
 * `AdvancedTextColumn` masks its state:
 *
 * ```php
 * $this->app->singleton(MasksText::class, MyCustomMasker::class);
 * ```
 */
interface MasksText
{
    /**
     * Replace part of the given text with a masking character.
     *
     * @param  string  $text  The plain text to mask.
     * @param  string  $character  The character used for masked positions.
     * @param  int  $index  Zero-based offset of the first masked character.
     *                      A negative index counts from the end of the string.
     * @param  int | null  $length  Number of characters to mask, or `null` to
     *                              mask through to the end of the string.
     */
    public function mask(string $text, string $character = '•', int $index = 0, ?int $length = null): string;

    /**
     * Mask the local part of an email address while keeping it recognizable,
     * e.g. `jane.doe@example.com` becomes `j•••••••@example.com`.
     */
    public function maskEmail(string $email, string $character = '•'): string;
}
