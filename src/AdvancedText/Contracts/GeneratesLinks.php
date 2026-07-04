<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts;

use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\LinkGenerator;

/**
 * Generates contact-scheme URLs (`mailto:`, `tel:`, WhatsApp) from a cell's
 * state.
 *
 * The default implementation is
 * {@see LinkGenerator}.
 * Rebind this contract in the container to change how every
 * `AdvancedTextColumn` builds its links.
 */
interface GeneratesLinks
{
    /**
     * Build a `mailto:` URL, or return `null` when the state is not a valid
     * email address.
     */
    public function mailto(string $email): ?string;

    /**
     * Build a `tel:` URL, keeping only characters that are meaningful when
     * dialing, or return `null` when nothing dialable remains.
     */
    public function tel(string $phone): ?string;

    /**
     * Build a WhatsApp click-to-chat URL (`https://wa.me/...`), optionally
     * with a prefilled message, or return `null` when the number contains no
     * digits.
     */
    public function whatsapp(string $phone, ?string $message = null): ?string;
}
