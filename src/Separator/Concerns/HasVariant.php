<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns;

use Closure;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Enums\SeparatorVariant;

/**
 * The separator's visual treatment — a line pattern (`solid`, `dashed`,
 * `dotted`) or tone (`default`, `subtle`, `muted`).
 *
 * Built-in names resolve to a {@see SeparatorVariant} case; any other string
 * is kept as-is, so registering a brand-new variant is just a matter of
 * calling `->variant('brand')` and styling the resulting
 * `fi-separator-variant-brand` class — no package changes required.
 */
trait HasVariant
{
    protected SeparatorVariant | string | Closure | null $variant = null;

    public function variant(SeparatorVariant | string | Closure | null $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Resets the separator to the package's default variant.
     *
     * Every {@see Component} already declares a
     * `default(mixed $state)` (for a field's default state), so this
     * override widens its parameter type to `mixed` purely to stay
     * signature-compatible — a `Separator` never has state, so nothing is
     * lost by repurposing the method name here.
     */
    public function default(mixed $condition = true): static
    {
        return $this->variant(fn (): ?SeparatorVariant => $this->evaluate($condition) ? SeparatorVariant::Default : null);
    }

    public function subtle(bool | Closure $condition = true): static
    {
        return $this->variant(fn (): ?SeparatorVariant => $this->evaluate($condition) ? SeparatorVariant::Subtle : null);
    }

    public function muted(bool | Closure $condition = true): static
    {
        return $this->variant(fn (): ?SeparatorVariant => $this->evaluate($condition) ? SeparatorVariant::Muted : null);
    }

    public function solid(bool | Closure $condition = true): static
    {
        return $this->variant(fn (): ?SeparatorVariant => $this->evaluate($condition) ? SeparatorVariant::Solid : null);
    }

    public function dashed(bool | Closure $condition = true): static
    {
        return $this->variant(fn (): ?SeparatorVariant => $this->evaluate($condition) ? SeparatorVariant::Dashed : null);
    }

    public function dotted(bool | Closure $condition = true): static
    {
        return $this->variant(fn (): ?SeparatorVariant => $this->evaluate($condition) ? SeparatorVariant::Dotted : null);
    }

    public function zigzag(bool | Closure $condition = true): static
    {
        return $this->variant(fn (): ?SeparatorVariant => $this->evaluate($condition) ? SeparatorVariant::Zigzag : null);
    }

    public function getVariant(): SeparatorVariant | string
    {
        $variant = $this->evaluate($this->variant);

        if ($variant instanceof SeparatorVariant) {
            return $variant;
        }

        if (blank($variant)) {
            return SeparatorVariant::Default;
        }

        return SeparatorVariant::tryFrom($variant) ?? $variant;
    }

    /**
     * The CSS class the renderer attaches to the root element, e.g.
     * `fi-separator-variant-dashed`. Arbitrary custom variant names are
     * slugged, so they can never break out of the class attribute.
     */
    public function getVariantClass(): string
    {
        $variant = $this->getVariant();

        $name = $variant instanceof SeparatorVariant ? $variant->value : Str::slug($variant);

        return "fi-separator-variant-{$name}";
    }
}
