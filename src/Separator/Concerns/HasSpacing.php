<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns;

use Closure;
use Filament\Support\Enums\Size;

/**
 * The whitespace around a separator: `margin()`/`padding()` set the usual
 * pair of values, while `spaceBefore()`/`spaceAfter()` override one side
 * independently — for a divider that should, say, hug the section above it
 * but breathe below.
 *
 * Every value accepts a {@see Size} preset, a raw CSS length (`'1.5rem'`),
 * or a bare integer treated as pixels — the same trio `HasMultiProgressBar`
 * uses for its own dimensions.
 */
trait HasSpacing
{
    protected Size | int | string | Closure | null $margin = null;

    protected Size | int | string | Closure | null $padding = null;

    protected Size | int | string | Closure | null $spaceBefore = null;

    protected Size | int | string | Closure | null $spaceAfter = null;

    /**
     * Preset lengths for the {@see Size} shorthands.
     *
     * @var array<string, string>
     */
    protected static array $spacingSizes = [
        'xs' => '0.5rem',
        'sm' => '0.75rem',
        'md' => '1rem',
        'lg' => '1.5rem',
        'xl' => '2rem',
    ];

    /**
     * The space before and after the separator. Overridden per-side by
     * {@see spaceBefore()} / {@see spaceAfter()}.
     */
    public function margin(Size | int | string | Closure | null $margin): static
    {
        $this->margin = $margin;

        return $this;
    }

    /**
     * The gap between the line and the centered label/icon.
     */
    public function padding(Size | int | string | Closure | null $padding): static
    {
        $this->padding = $padding;

        return $this;
    }

    /**
     * The space before the separator, overriding {@see margin()}.
     */
    public function spaceBefore(Size | int | string | Closure | null $space): static
    {
        $this->spaceBefore = $space;

        return $this;
    }

    /**
     * The space after the separator, overriding {@see margin()}.
     */
    public function spaceAfter(Size | int | string | Closure | null $space): static
    {
        $this->spaceAfter = $space;

        return $this;
    }

    public function getMargin(): ?string
    {
        return $this->resolveSpacing($this->margin);
    }

    public function getPadding(): ?string
    {
        return $this->resolveSpacing($this->padding);
    }

    public function getSpaceBefore(): ?string
    {
        return $this->resolveSpacing($this->spaceBefore) ?? $this->getMargin();
    }

    public function getSpaceAfter(): ?string
    {
        return $this->resolveSpacing($this->spaceAfter) ?? $this->getMargin();
    }

    protected function resolveSpacing(Size | int | string | Closure | null $value): ?string
    {
        $value = $this->evaluate($value);

        if ($value instanceof Size) {
            $value = $value->value;
        }

        if (blank($value)) {
            return null;
        }

        if (is_int($value)) {
            return "{$value}px";
        }

        return static::$spacingSizes[$value] ?? $value;
    }
}
