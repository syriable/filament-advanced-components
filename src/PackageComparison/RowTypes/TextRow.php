<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * A single-line free-text cell per package.
 */
class TextRow extends RowType
{
    public function getName(): string
    {
        return 'text';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-pencil-square';
    }

    public function getDefaultValue(): mixed
    {
        return '';
    }

    public function normalizeValue(mixed $value, array $config): mixed
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.text';
    }
}
