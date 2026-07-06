<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Separator\Concerns;

use Closure;
use Filament\Schemas\Components\Text;
use Filament\Support\Concerns\HasFontFamily;
use Filament\Support\Concerns\HasWeight;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;

/**
 * Typography controls for the separator's optional label, mirroring Filament's
 * schema {@see Text} API — `size()`, `weight()`,
 * and `fontFamily()`.
 */
trait HasLabelTypography
{
    use HasFontFamily;
    use HasWeight;

    protected TextSize | Size | string | Closure | null $size = null;

    public function size(TextSize | Size | string | Closure | null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): TextSize | Size | string | null
    {
        $size = $this->evaluate($this->size);

        if (blank($size)) {
            return null;
        }

        if (is_string($size)) {
            return TextSize::tryFrom($size) ?? $size;
        }

        return $size;
    }

    /**
     * @return list<string>
     */
    public function getLabelTypographyClasses(): array
    {
        $classes = [];

        $size = $this->getSize();

        if (filled($size)) {
            $classes[] = ($size instanceof TextSize) ? "fi-size-{$size->value}" : (string) $size;
        }

        $weight = $this->getWeight();

        if (filled($weight)) {
            $classes[] = ($weight instanceof FontWeight) ? "fi-font-{$weight->value}" : (string) $weight;
        }

        $fontFamily = $this->getFontFamily();

        if (filled($fontFamily)) {
            $classes[] = ($fontFamily instanceof FontFamily) ? "fi-font-{$fontFamily->value}" : (string) $fontFamily;
        }

        return $classes;
    }
}
