<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * An included / not-included checkmark cell per package.
 */
class BooleanRow extends RowType
{
    public function getName(): string
    {
        return 'boolean';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-check-circle';
    }

    public function getDefaultValue(): mixed
    {
        return false;
    }

    public function normalizeValue(mixed $value, array $config): mixed
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.boolean';
    }
}
