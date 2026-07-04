<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * A multi-line free-text cell per package.
 */
class TextareaRow extends TextRow
{
    public function getName(): string
    {
        return 'textarea';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array
    {
        return [
            'rows' => 3,
        ];
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.textarea';
    }
}
