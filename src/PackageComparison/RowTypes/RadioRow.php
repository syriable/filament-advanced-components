<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * The same option plumbing as {@see SelectRow}, rendered as a stacked radio
 * group inside each cell instead of a dropdown.
 */
class RadioRow extends SelectRow
{
    public function getName(): string
    {
        return 'radio';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-list-bullet';
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.radio';
    }
}
