<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Options;

use Syriable\Filament\Plugins\AdvancedComponents\AdvancedSelect\Enums\BadgeAlignment;

/**
 * The fully evaluated, render-ready state of one {@see SelectOption}.
 *
 * By the time this object exists every lazy value — label, description, icon,
 * colors, disabled/visible flags — has been resolved against the owning
 * component's evaluation context. The renderer only assembles markup from it,
 * so evaluation and rendering stay independent (see the `RendersOptions`
 * contract) and each option costs a single small immutable value object.
 */
readonly class OptionViewModel
{
    /**
     * @param  string  $value  The stringified option value stored in state.
     * @param  string  $label  The plain-text label (already translated / escaped-safe input).
     * @param  string | null  $description  Secondary line shown under the label.
     * @param  string  $iconHtml  Pre-rendered leading icon markup, or an empty string.
     * @param  string | array<int | string, string | int> | null  $color  Filament color for the label tint and badge.
     * @param  string | null  $badgeLabel  Trailing badge text, or null for no badge.
     * @param  string | array<int | string, string | int> | null  $badgeColor  Badge color; falls back to {@see $color}.
     * @param  BadgeAlignment  $badgeAlign  Where the badge sits on the label line.
     * @param  bool  $isDisabled  Whether the option is selectable.
     * @param  string | null  $group  Optgroup label, or null for a top-level option.
     * @param  array<string>  $extraClasses  Extra CSS classes on the option element.
     * @param  array<string, mixed>  $extraAttributes  Extra HTML attributes on the option element.
     */
    public function __construct(
        public string $value,
        public string $label,
        public ?string $description = null,
        public string $iconHtml = '',
        public string | array | null $color = null,
        public ?string $badgeLabel = null,
        public string | array | null $badgeColor = null,
        public BadgeAlignment $badgeAlign = BadgeAlignment::Start,
        public bool $isDisabled = false,
        public ?string $group = null,
        public array $extraClasses = [],
        public array $extraAttributes = [],
    ) {}

    public function badgeAtEnd(): bool
    {
        return $this->badgeAlign === BadgeAlignment::End;
    }

    public function hasIcon(): bool
    {
        return $this->iconHtml !== '';
    }

    public function hasDescription(): bool
    {
        return $this->description !== null && $this->description !== '';
    }

    public function hasBadge(): bool
    {
        return $this->badgeLabel !== null && $this->badgeLabel !== '';
    }

    /**
     * The plain-text haystack used when filtering options by a search term.
     */
    public function searchableText(): string
    {
        return trim($this->label . ' ' . ($this->description ?? '') . ' ' . ($this->badgeLabel ?? ''));
    }
}
