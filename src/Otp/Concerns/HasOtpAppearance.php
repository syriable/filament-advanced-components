<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Otp\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpShape;
use Syriable\Filament\Plugins\AdvancedComponents\Otp\Enums\OtpSize;

/**
 * Presentation-only knobs: cell {@see OtpSize size}, {@see OtpShape corner
 * style}, and optional overrides for cell width and inter-cell gap. Each maps
 * to a class or CSS custom property consumed by the stylesheet, so themes can
 * restyle everything without touching markup.
 */
trait HasOtpAppearance
{
    protected OtpSize | string | Closure $size = OtpSize::Medium;

    protected OtpShape | string | Closure $shape = OtpShape::Rounded;

    protected int | string | Closure | null $cellWidth = null;

    protected int | string | Closure | null $gap = null;

    public function size(OtpSize | string | Closure $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function compact(bool | Closure $condition = true): static
    {
        return $this->size(fn (): OtpSize => $this->evaluate($condition) ? OtpSize::Compact : OtpSize::Medium);
    }

    public function small(bool | Closure $condition = true): static
    {
        return $this->size(fn (): OtpSize => $this->evaluate($condition) ? OtpSize::Small : OtpSize::Medium);
    }

    public function large(bool | Closure $condition = true): static
    {
        return $this->size(fn (): OtpSize => $this->evaluate($condition) ? OtpSize::Large : OtpSize::Medium);
    }

    public function shape(OtpShape | string | Closure $shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function rounded(bool | Closure $condition = true): static
    {
        return $this->shape(fn (): OtpShape => $this->evaluate($condition) ? OtpShape::Rounded : OtpShape::Square);
    }

    public function square(bool | Closure $condition = true): static
    {
        return $this->shape(fn (): OtpShape => $this->evaluate($condition) ? OtpShape::Square : OtpShape::Rounded);
    }

    /**
     * An explicit cell width. Integers are pixels; strings are raw CSS
     * lengths. Overrides the {@see size() size} preset's width.
     */
    public function cellWidth(int | string | Closure | null $width): static
    {
        $this->cellWidth = $width;

        return $this;
    }

    /**
     * The gap between cells. Integers are pixels; strings are raw CSS
     * lengths.
     *
     * Named `cellGap()` (not `gap()`) because the base schema `Component`
     * already owns `gap()` for the layout gap around the whole field, and
     * that must keep working.
     */
    public function cellGap(int | string | Closure | null $gap): static
    {
        $this->gap = $gap;

        return $this;
    }

    public function getSize(): OtpSize
    {
        $size = $this->evaluate($this->size);

        if ($size instanceof OtpSize) {
            return $size;
        }

        return OtpSize::tryFrom((string) $size) ?? OtpSize::Medium;
    }

    public function getShape(): OtpShape
    {
        $shape = $this->evaluate($this->shape);

        if ($shape instanceof OtpShape) {
            return $shape;
        }

        return OtpShape::tryFrom((string) $shape) ?? OtpShape::Rounded;
    }

    public function getCellWidth(): ?string
    {
        return $this->toCssLength($this->evaluate($this->cellWidth));
    }

    public function getGap(): ?string
    {
        return $this->toCssLength($this->evaluate($this->gap));
    }

    protected function toCssLength(int | string | null $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return is_int($value) ? "{$value}px" : $value;
    }
}
