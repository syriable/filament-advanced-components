<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * A numeric cell per package (e.g. "Number of Products").
 */
class NumberRow extends RowType
{
    public function getName(): string
    {
        return 'number';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-hashtag';
    }

    public function getDefaultValue(): mixed
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array
    {
        return [
            'min' => null,
            'max' => null,
            'step' => null,
        ];
    }

    public function normalizeValue(mixed $value, array $config): mixed
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (float) $value;

        if (is_numeric($config['min'] ?? null)) {
            $value = max((float) $config['min'], $value);
        }

        if (is_numeric($config['max'] ?? null)) {
            $value = min((float) $config['max'], $value);
        }

        return floor($value) === $value ? (int) $value : $value;
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.number';
    }
}
