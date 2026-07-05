<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns;

use Closure;
use Filament\Support\Concerns\HasIconPosition;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Enums\Orientation;

/**
 * The axis a separator is drawn along, following the same "nullable toggle
 * falls back to a sensible default" shape as Filament's own
 * {@see HasIconPosition}.
 */
trait HasOrientation
{
    protected Orientation | string | Closure | null $orientation = null;

    public function orientation(Orientation | string | Closure | null $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * A single line spanning the full width of its container, with the
     * optional label/icon splitting it. The default.
     */
    public function horizontal(bool | Closure $condition = true): static
    {
        return $this->orientation(fn (): ?Orientation => $this->evaluate($condition) ? Orientation::Horizontal : null);
    }

    /**
     * A single line spanning the full height of its container — for
     * dividing side-by-side content inside a `Flex` or `Grid`.
     */
    public function vertical(bool | Closure $condition = true): static
    {
        return $this->orientation(fn (): ?Orientation => $this->evaluate($condition) ? Orientation::Vertical : null);
    }

    public function getOrientation(): Orientation
    {
        $orientation = $this->evaluate($this->orientation);

        if ($orientation instanceof Orientation) {
            return $orientation;
        }

        if (blank($orientation)) {
            return Orientation::Horizontal;
        }

        return Orientation::tryFrom($orientation) ?? Orientation::Horizontal;
    }

    public function isVertical(): bool
    {
        return $this->getOrientation() === Orientation::Vertical;
    }
}
