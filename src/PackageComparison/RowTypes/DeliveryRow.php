<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * A composite "delivery time" cell per package: an amount plus a unit
 * (`{"amount": 7, "unit": "days"}`), like Fiverr's delivery row.
 */
class DeliveryRow extends RowType
{
    public function getName(): string
    {
        return 'delivery';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-truck';
    }

    public function getDefaultValue(): mixed
    {
        return [
            'amount' => null,
            'unit' => 'days',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array
    {
        return [
            'units' => ['days', 'hours'],
        ];
    }

    public function normalizeConfig(mixed $config): array
    {
        $config = parent::normalizeConfig($config);

        $units = is_array($config['units']) ? $config['units'] : [];

        $units = array_values(array_unique(array_filter(
            array_map(static fn (mixed $unit): string => is_scalar($unit) ? trim((string) $unit) : '', $units),
            static fn (string $unit): bool => $unit !== '',
        )));

        $config['units'] = ($units === []) ? ['days', 'hours'] : $units;

        return $config;
    }

    public function normalizeValue(mixed $value, array $config): mixed
    {
        $units = $config['units'] ?? ['days', 'hours'];

        $amount = is_array($value) && is_numeric($value['amount'] ?? null)
            ? max(0, (int) $value['amount'])
            : null;

        $unit = is_array($value) && in_array($value['unit'] ?? null, $units, strict: true)
            ? $value['unit']
            : $units[0];

        return [
            'amount' => $amount,
            'unit' => $unit,
        ];
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.delivery';
    }
}
