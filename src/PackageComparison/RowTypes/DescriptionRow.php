<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * A taller textarea meant for the "what's included" paragraph each package
 * gets at the top of a Fiverr-style comparison table.
 */
class DescriptionRow extends TextareaRow
{
    public function getName(): string
    {
        return 'description';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array
    {
        return [
            'rows' => 4,
        ];
    }
}
