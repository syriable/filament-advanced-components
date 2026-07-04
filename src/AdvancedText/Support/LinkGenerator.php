<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support;

use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\GeneratesLinks;

/**
 * Default {@see GeneratesLinks} implementation.
 *
 * Every generator is defensive: given state that cannot produce a meaningful
 * link (an invalid email, a phone number without digits) it returns `null`
 * and the column renders plain text instead of a broken link.
 */
class LinkGenerator implements GeneratesLinks
{
    public function mailto(string $email): ?string
    {
        $email = trim($email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return 'mailto:' . $email;
    }

    public function tel(string $phone): ?string
    {
        // RFC 3966 allows digits, visual separators, and a leading plus.
        $dialable = preg_replace('/[^0-9+#*]/', '', $phone) ?? '';

        if (preg_replace('/[^0-9]/', '', $dialable) === '') {
            return null;
        }

        return 'tel:' . $dialable;
    }

    public function whatsapp(string $phone, ?string $message = null): ?string
    {
        // wa.me accepts the full international number, digits only.
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        $url = 'https://wa.me/' . $digits;

        if (filled($message)) {
            $url .= '?text=' . rawurlencode($message);
        }

        return $url;
    }
}
