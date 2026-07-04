<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * A short call-to-action / footnote line per package, visually anchored to
 * the bottom of the column like the "Select" footer on Fiverr's table.
 */
class FooterRow extends TextRow
{
    public function getName(): string
    {
        return 'footer';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-rectangle-group';
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.footer';
    }
}
