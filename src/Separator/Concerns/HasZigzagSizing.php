<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns;

use Closure;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Enums\SeparatorVariant;

/**
 * Controls the conic-gradient zigzag geometry via CSS custom properties on
 * the separator root — {@see zigzagSize()}, {@see zigzagDepth()}, and
 * {@see zigzagAngle()}. Pair with {@see HasThickness::thick()} /
 * {@see HasThickness::thin()} for presets on the zigzag variant.
 */
trait HasZigzagSizing
{
    protected int | string | Closure | null $zigzagSize = null;

    protected int | string | Closure | null $zigzagDepth = null;

    protected int | string | Closure | null $zigzagAngle = null;

    /**
     * The tooth period (`--fi-separator-zigzag-s`).
     */
    public function zigzagSize(int | string | Closure | null $size): static
    {
        $this->zigzagSize = $size;

        return $this;
    }

    /**
     * The tooth depth / amplitude (`--fi-separator-zigzag-b`).
     */
    public function zigzagDepth(int | string | Closure | null $depth): static
    {
        $this->zigzagDepth = $depth;

        return $this;
    }

    /**
     * The tooth angle (`--fi-separator-zigzag-a`), e.g. `90` or `'90deg'`.
     */
    public function zigzagAngle(int | string | Closure | null $angle): static
    {
        $this->zigzagAngle = $angle;

        return $this;
    }

    public function getZigzagSize(): ?string
    {
        $size = $this->resolveZigzagLength($this->zigzagSize);

        if (filled($size)) {
            return $size;
        }

        if ($this->getVariant() !== SeparatorVariant::Zigzag) {
            return null;
        }

        if ($this->hasThickPreset()) {
            return '100px';
        }

        if ($this->hasThinPreset()) {
            return '4px';
        }

        return null;
    }

    public function getZigzagDepth(): ?string
    {
        $depth = $this->resolveZigzagLength($this->zigzagDepth);

        if (filled($depth)) {
            return $depth;
        }

        if ($this->getVariant() !== SeparatorVariant::Zigzag) {
            return null;
        }

        if ($this->hasThickPreset()) {
            return '35px';
        }

        if ($this->hasThinPreset()) {
            return '1px';
        }

        return null;
    }

    public function getZigzagAngle(): ?string
    {
        return $this->resolveZigzagAngle($this->zigzagAngle);
    }

    /**
     * Resolved CSS custom properties for the zigzag geometry, injected on
     * the separator root element.
     *
     * @return array<string, string>
     */
    public function getZigzagCssVariables(): array
    {
        if ($this->getVariant() !== SeparatorVariant::Zigzag) {
            return [];
        }

        $variables = [];

        $size = $this->getZigzagSize();
        $depth = $this->getZigzagDepth();

        if (filled($size)) {
            $variables['--fi-separator-zigzag-s'] = $size;
        }

        if (filled($depth)) {
            $variables['--fi-separator-zigzag-b'] = $depth;
        }

        $angle = $this->getZigzagAngle();

        if (filled($angle)) {
            $variables['--fi-separator-zigzag-a'] = $angle;
        }

        return $variables;
    }

    protected function resolveZigzagLength(int | string | Closure | null $value): ?string
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

    protected function resolveZigzagAngle(int | string | Closure | null $value): ?string
    {
        $value = $this->evaluate($value);

        if (blank($value)) {
            return null;
        }

        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            return "{$value}deg";
        }

        return $value;
    }
}
