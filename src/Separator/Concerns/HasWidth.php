<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns;

use Closure;
use Filament\Schemas\Components\Component;

/**
 * The separator's own width — distinct from the schema-level
 * `maxWidth()` that every {@see Component}
 * already inherits (that one caps the outer grid cell; this one sizes the
 * line itself, e.g. a short horizontal rule that doesn't span its column).
 */
trait HasWidth
{
    protected int | string | Closure | null $width = null;

    public function width(int | string | Closure | null $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Resets the separator to span the full width (or, when
     * {@see HasOrientation::vertical()}
     * is used, the full height) of its container. This is the default, so
     * it only matters after an earlier {@see width()} call.
     */
    public function fullWidth(bool | Closure $condition = true): static
    {
        return $this->width(fn (): ?string => $this->evaluate($condition) ? '100%' : null);
    }

    public function getWidth(): ?string
    {
        $width = $this->evaluate($this->width);

        if (is_int($width)) {
            return "{$width}px";
        }

        return filled($width) ? $width : null;
    }
}
