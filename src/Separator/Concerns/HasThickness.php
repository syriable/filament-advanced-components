<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns;

use Closure;

/**
 * Line weight for every divider variant — border-based lines use
 * `--fi-separator-thickness`; {@see HasZigzagSizing} maps the same
 * {@see thick()} / {@see thin()} presets to zigzag geometry when the
 * zigzag variant is active.
 */
trait HasThickness
{
    protected int | string | Closure | null $thickness = null;

    protected bool $thickPreset = false;

    protected bool $thinPreset = false;

    /**
     * Sets an explicit line thickness (`--fi-separator-thickness`).
     */
    public function thickness(int | string | Closure | null $thickness): static
    {
        $this->thickPreset = false;
        $this->thinPreset = false;
        $this->thickness = $thickness;

        return $this;
    }

    /**
     * A heavier divider. On border variants (solid, dashed, dotted, …) this
     * defaults to `3px`. On {@see HasVariant::zigzag()} it defaults to a
     * large decorative tooth pattern (`100px` / `35px`) unless overridden
     * with two arguments:
     *
     * ```php
     * Separator::make()->dashed()->thick();
     * Separator::make()->dashed()->thick(5);
     * Separator::make()->zigzag()->thick();
     * Separator::make()->zigzag()->thick(60, 20);
     * ```
     */
    public function thick(int | string | Closure | null $thickness = null, int | string | Closure | null $zigzagDepth = null): static
    {
        $this->thickPreset = true;
        $this->thinPreset = false;

        if ($zigzagDepth !== null) {
            $this->zigzagSize($thickness ?? 100)->zigzagDepth($zigzagDepth);

            return $this;
        }

        $this->thickness = $thickness ?? 3;

        return $this;
    }

    /**
     * A hairline divider — `1px` on border variants, compact zigzag teeth
     * when combined with {@see HasVariant::zigzag()}.
     */
    public function thin(int | string | Closure | null $thickness = null): static
    {
        $this->thickPreset = false;
        $this->thinPreset = true;
        $this->thickness = $thickness ?? 1;

        return $this;
    }

    public function hasThickPreset(): bool
    {
        return $this->thickPreset;
    }

    public function hasThinPreset(): bool
    {
        return $this->thinPreset;
    }

    public function getThickness(): ?string
    {
        return $this->resolveThicknessLength($this->thickness);
    }

    protected function resolveThicknessLength(int | string | Closure | null $value): ?string
    {
        $value = $this->evaluate($value);

        if (blank($value)) {
            return null;
        }

        if (is_int($value)) {
            return "{$value}px";
        }

        return $value;
    }
}
