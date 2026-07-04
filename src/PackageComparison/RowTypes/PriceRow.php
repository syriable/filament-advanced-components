<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * A price cell per package, rendered with the row's currency prefix.
 */
class PriceRow extends NumberRow
{
    public function getName(): string
    {
        return 'price';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'min' => 0,
            'currency' => 'USD',
        ];
    }

    public function normalizeConfig(mixed $config): array
    {
        $config = parent::normalizeConfig($config);

        $config['currency'] = is_string($config['currency']) && ($config['currency'] !== '')
            ? mb_strtoupper(trim($config['currency']))
            : 'USD';

        return $config;
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.price';
    }

    public function getSettingsView(): ?string
    {
        return 'filament-advanced-components::components.package-comparison.settings.price';
    }
}
