<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\PackageComparison\RowTypes;

/**
 * A dropdown cell per package. The choices live on the row itself
 * (`rows[].config.options`, a list of strings) and are edited inline through
 * the row's settings popover, so two select rows can offer different options.
 */
class SelectRow extends RowType
{
    public function getName(): string
    {
        return 'select';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-chevron-up-down';
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
            'options' => [],
        ];
    }

    public function normalizeConfig(mixed $config): array
    {
        $config = parent::normalizeConfig($config);

        $options = is_array($config['options']) ? $config['options'] : [];

        $config['options'] = array_values(array_unique(array_filter(
            array_map(static fn (mixed $option): string => is_scalar($option) ? trim((string) $option) : '', $options),
            static fn (string $option): bool => $option !== '',
        )));

        return $config;
    }

    public function normalizeValue(mixed $value, array $config): mixed
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = (string) $value;

        return in_array($value, $config['options'] ?? [], strict: true) ? $value : null;
    }

    public function getCellView(): string
    {
        return 'filament-advanced-components::components.package-comparison.cells.select';
    }

    public function getSettingsView(): ?string
    {
        return 'filament-advanced-components::components.package-comparison.settings.options';
    }
}
