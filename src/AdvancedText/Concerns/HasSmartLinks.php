<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\GeneratesLinks;

/**
 * Turns the cell into a contact link generated from its own state.
 *
 * ```php
 * AdvancedTextColumn::make('email')->mailable();
 * AdvancedTextColumn::make('phone')->callable();
 * AdvancedTextColumn::make('phone')->whatsappable(message: 'Hi there!');
 * ```
 *
 * An explicit `url()` always wins over a generated link, so these helpers
 * never override a manual configuration. Invalid states (a malformed email,
 * a phone number without digits) degrade gracefully to plain text.
 */
trait HasSmartLinks
{
    protected bool | Closure $isMailable = false;

    protected bool | Closure $isCallable = false;

    protected bool | Closure $isWhatsappable = false;

    protected string | Closure | null $whatsappMessage = null;

    /**
     * Link the cell to `mailto:` its own state.
     */
    public function mailable(bool | Closure $condition = true): static
    {
        $this->isMailable = $condition;

        return $this;
    }

    /**
     * Link the cell to `tel:` its own state.
     */
    public function callable(bool | Closure $condition = true): static
    {
        $this->isCallable = $condition;

        return $this;
    }

    /**
     * Link the cell to a WhatsApp click-to-chat URL for its own state,
     * optionally prefilling a message.
     */
    public function whatsappable(bool | Closure $condition = true, string | Closure | null $message = null): static
    {
        $this->isWhatsappable = $condition;

        if ($message !== null) {
            $this->whatsappMessage = $message;
        }

        return $this;
    }

    /**
     * The message prefilled in the WhatsApp chat opened by `whatsappable()`.
     */
    public function whatsappMessage(string | Closure | null $message): static
    {
        $this->whatsappMessage = $message;

        return $this;
    }

    public function isMailable(): bool
    {
        return (bool) $this->evaluate($this->isMailable);
    }

    public function isCallable(): bool
    {
        return (bool) $this->evaluate($this->isCallable);
    }

    public function isWhatsappable(): bool
    {
        return (bool) $this->evaluate($this->isWhatsappable);
    }

    public function getWhatsappMessage(): ?string
    {
        $message = $this->evaluate($this->whatsappMessage);

        return $message === null ? null : (string) $message;
    }

    /**
     * Generate the contact link for the given state, or `null` when no
     * contact link behavior is enabled or the state cannot produce one.
     * Precedence: mail, then phone, then WhatsApp.
     */
    public function getSmartLinkUrl(mixed $state): ?string
    {
        if (! is_scalar($state)) {
            return null;
        }

        $state = trim((string) $state);

        if ($state === '') {
            return null;
        }

        $links = app(GeneratesLinks::class);

        if ($this->isMailable()) {
            return $links->mailto($state);
        }

        if ($this->isCallable()) {
            return $links->tel($state);
        }

        if ($this->isWhatsappable()) {
            return $links->whatsapp($state, $this->getWhatsappMessage());
        }

        return null;
    }

    protected function hasSmartLink(): bool
    {
        if ($this->isMailable()) {
            return true;
        }
        if ($this->isCallable()) {
            return true;
        }

        return (bool) $this->isWhatsappable();
    }
}
